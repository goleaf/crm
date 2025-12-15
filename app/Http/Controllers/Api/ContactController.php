<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Models\People;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ContactController extends Controller
{
    /**
     * Display a listing of contacts.
     */
    public function index(Request $request): JsonResponse
    {
        $contacts = People::query()
            ->with(['company', 'persona'])
            ->where('team_id', auth()->user()->currentTeam->id)
            ->when($request->search, function ($query, $search): void {
                $query->where(function (\Illuminate\Contracts\Database\Query\Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->per_page ?? 25);

        return response()->json($contacts);
    }

    /**
     * Store a new contact.
     * Supports precognitive validation.
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        // Precognitive requests stop here after validation
        // Only actual submissions reach this point

        $contact = People::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'mobile' => $request->mobile,
            'company_id' => $request->company_id,
            'title' => $request->title,
            'department' => $request->department,
            'persona_id' => $request->persona_id,
            'address' => $request->address,
            'team_id' => auth()->user()->currentTeam->id,
        ]);

        return response()->json([
            'message' => __('app.messages.contact_created'),
            'data' => $contact->load(['company', 'persona']),
        ], 201);
    }

    /**
     * Display the specified contact.
     */
    public function show(People $contact): JsonResponse
    {
        $this->authorize('view', $contact);

        return response()->json([
            'data' => $contact->load(['company', 'persona', 'roles', 'communicationPreferences']),
        ]);
    }

    /**
     * Update the specified contact.
     * Supports precognitive validation.
     */
    public function update(UpdateContactRequest $request, People $contact): JsonResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return response()->json([
            'message' => __('app.messages.contact_updated'),
            'data' => $contact->load(['company', 'persona']),
        ]);
    }

    /**
     * Remove the specified contact.
     */
    public function destroy(People $contact): JsonResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return response()->json([
            'message' => __('app.messages.contact_deleted'),
        ]);
    }
}
