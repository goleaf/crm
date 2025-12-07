<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\PeopleRepositoryInterface;
use App\Enums\AccountType;
use App\Enums\Industry;
use App\Filament\Resources\KnowledgeArticleResource\RelationManagers\ApprovalsRelationManager;
use App\Http\Responses\LoginResponse;
use App\Models\Account;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Document;
use App\Models\DocumentShare;
use App\Models\DocumentTemplate;
use App\Models\DocumentVersion;
use App\Models\Extension;
use App\Models\Import;
use App\Models\Invoice;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeArticleComment;
use App\Models\KnowledgeArticleRelation;
use App\Models\KnowledgeArticleVersion;
use App\Models\KnowledgeCategory;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeTag;
use App\Models\KnowledgeTemplateResponse;
use App\Models\Lead;
use App\Models\Note;
use App\Models\Opportunity;
use App\Models\Order;
use App\Models\People;
use App\Models\ProcessDefinition;
use App\Models\ProcessExecution;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\SupportCase;
use App\Models\Tag;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use App\Repositories\EloquentCompanyRepository;
use App\Repositories\EloquentPeopleRepository;
use App\Services\GitHubService;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Livewire;
use Relaticle\SystemAdmin\Models\SystemAdministrator;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\Filament\Auth\Http\Responses\Contracts\LoginResponse::class, LoginResponse::class);
        $this->app->bind(PeopleRepositoryInterface::class, EloquentPeopleRepository::class);
        $this->app->bind(CompanyRepositoryInterface::class, EloquentCompanyRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePolicies();
        $this->configureAuthorization();
        $this->configureModels();
        $this->configureFilament();
        $this->configureGitHubStars();
        $this->configureLivewire();
        $this->configureMailViews();
        $this->configureTranslations();
        $this->configureCompanyConfig();
        $this->shareUiTranslations();
    }

    /**
     * Configure translation overrides.
     */
    private function configureTranslations(): void
    {
        // Use package translations; overrides can be placed in resources/lang/vendor/custom-fields if needed.
    }

    /**
     * Populate translated company config options once the translator is available.
     */
    private function configureCompanyConfig(): void
    {
        if (! $this->app->bound('translator')) {
            return;
        }

        config([
            'company.account_types' => AccountType::options(),
            'company.industries' => Industry::options(),
        ]);
    }

    /**
     * Make UI translations available to front-end (Filament) views.
     */
    private function shareUiTranslations(): void
    {
        Facades\View::share('uiTranslations', fn (): array => trans('ui'));
    }

    private function configurePolicies(): void
    {
        Gate::guessPolicyNamesUsing(function (string $modelClass): string {
            $guesses = [];

            try {
                $currentPanelId = Filament::getCurrentPanel()?->getId();

                if ($currentPanelId === 'sysadmin') {
                    $modelName = class_basename($modelClass);
                    $systemAdminPolicy = "Relaticle\\SystemAdmin\\Policies\\{$modelName}Policy";

                    if (class_exists($systemAdminPolicy)) {
                        $guesses[] = $systemAdminPolicy;
                    }
                }
            } catch (\Throwable) {
                // Fallback for non-Filament contexts
            }

            $guesses = [...$guesses, ...$this->getDefaultLaravelPolicyNames($modelClass)];
            $existing = collect($guesses)->first(fn (string $class): bool => class_exists($class));

            return $existing ?? $guesses[0];
        });
    }

    private function getDefaultLaravelPolicyNames(string $modelClass): array
    {
        // Replicate Laravel's default policy discovery logic from Gate.php:723-736
        $classDirname = str_replace('/', '\\', dirname(str_replace('\\', '/', $modelClass)));
        $classDirnameSegments = explode('\\', $classDirname);

        $guesses = collect(range(1, count($classDirnameSegments)))
            ->map(fn (int $index): string => implode('\\', array_slice($classDirnameSegments, 0, $index)).'\\Policies\\'.class_basename($modelClass).'Policy');

        // Add Models-specific paths if the model is in a Models directory
        if (str_contains($classDirname, '\\Models\\')) {
            $guesses = $guesses
                ->concat([str_replace('\\Models\\', '\\Policies\\', $classDirname).'\\'.class_basename($modelClass).'Policy'])
                ->concat([str_replace('\\Models\\', '\\Models\\Policies\\', $classDirname).'\\'.class_basename($modelClass).'Policy']);
        }

        // Return the first existing class, or fallback
        $existingPolicy = $guesses->reverse()->first(fn (string $class): bool => class_exists($class));

        return [$existingPolicy ?: $classDirname.'\\Policies\\'.class_basename($modelClass).'Policy'];
    }

    private function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability, array $arguments = []) {
            $tenantId = Filament::getTenant()?->getKey() ?? $user->currentTeam?->getKey();

            if ($tenantId !== null) {
                setPermissionsTeamId($tenantId);
            }

            $superRoles = config('permission.defaults.super_admin_roles', ['admin']);

            if ($user->hasAnyRole($superRoles)) {
                return true;
            }

            if (str_contains($ability, '.')) {
                return $user->can($ability);
            }

            $permission = $this->mapAbilityToPermission($ability, $arguments[0] ?? null);

            return $permission ? $user->can($permission) : null;
        });
    }

    private function mapAbilityToPermission(string $ability, mixed $resource): ?string
    {
        $map = [
            'viewAny' => 'view',
            'view' => 'view',
            'create' => 'create',
            'update' => 'update',
            'delete' => 'delete',
            'deleteAny' => 'delete',
            'restore' => 'restore',
            'restoreAny' => 'restore',
            'forceDelete' => 'force-delete',
            'forceDeleteAny' => 'force-delete',
        ];

        if (! isset($map[$ability])) {
            return null;
        }

        $class = $resource instanceof Model ? $resource::class : (is_string($resource) ? $resource : null);

        if ($class === null) {
            return null;
        }

        $resourceSlug = Str::kebab(Str::plural(class_basename($class)));

        return "{$resourceSlug}.{$map[$ability]}";
    }

    /**
     * Configure custom Livewire components.
     */
    private function configureLivewire(): void
    {
        $components = [
            ApprovalsRelationManager::class,
        ];

        foreach ($components as $component) {
            if (class_exists($component)) {
                Livewire::component($component, $component);
            }
        }
    }

    /**
     * Configure mail view paths.
     */
    private function configureMailViews(): void
    {
        // Override Laravel's default mail component namespace to use custom components
        Facades\Blade::componentNamespace('App\\View\\Components\\Mail', 'mail');
    }

    /**
     * Configure the models for the application.
     */
    private function configureModels(): void
    {
        Model::unguard();
        //        Model::shouldBeStrict(! $this->app->isProduction()); // TODO: Uncomment this line to enable strict mode in production

        Relation::enforceMorphMap([
            'team' => Team::class,
            'user' => User::class,
            'people' => People::class,
            'company' => Company::class,
            'opportunity' => Opportunity::class,
            'task' => Task::class,
            'note' => Note::class,
            'support_case' => SupportCase::class,
            'system_administrator' => SystemAdministrator::class,
            'import' => Import::class,
            'knowledge_article' => KnowledgeArticle::class,
            'knowledge_article_comment' => KnowledgeArticleComment::class,
            'knowledge_article_relation' => KnowledgeArticleRelation::class,
            'knowledge_article_version' => KnowledgeArticleVersion::class,
            'knowledge_category' => KnowledgeCategory::class,
            'knowledge_tag' => KnowledgeTag::class,
            'knowledge_faq' => KnowledgeFaq::class,
            'knowledge_template_response' => KnowledgeTemplateResponse::class,
            'lead' => Lead::class,
            'account' => Account::class,
            'invoice' => Invoice::class,
            'quote' => Quote::class,
            'document' => Document::class,
            'document_template' => DocumentTemplate::class,
            'document_version' => DocumentVersion::class,
            'document_share' => DocumentShare::class,
            'order' => Order::class,
            'delivery' => Delivery::class,
            'purchase_order' => PurchaseOrder::class,
            'product' => Product::class,
            'product_category' => ProductCategory::class,
            'tag' => Tag::class,
            'customer' => Customer::class,
            'activity' => Activity::class,
            'process_definition' => ProcessDefinition::class,
            'process_execution' => ProcessExecution::class,
            'extension' => Extension::class,
        ]);

        // Bind our custom Import model to the Filament Import model
        $this->app->bind(\Filament\Actions\Imports\Models\Import::class, Import::class);
    }

    /**
     * Configure Filament.
     */
    private function configureFilament(): void
    {
        $slideOverActions = ['create', 'edit', 'view'];

        Action::configureUsing(function (Action $action) use ($slideOverActions): Action {
            if (in_array($action->getName(), $slideOverActions)) {
                return $action->slideOver();
            }

            return $action;
        });
    }

    /**
     * Configure GitHub stars count.
     */
    private function configureGitHubStars(): void
    {
        // Share GitHub stars count with the header component
        Facades\View::composer('components.layout.header', function (View $view): void {
            $gitHubService = app(GitHubService::class);
            $starsCount = $gitHubService->getStarsCount();
            $formattedStarsCount = $gitHubService->getFormattedStarsCount();

            $view->with([
                'githubStars' => $starsCount,
                'formattedGithubStars' => $formattedStarsCount,
            ]);
        });
    }
}
