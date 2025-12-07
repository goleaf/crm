# Knowledge Base

Status: Implemented

Coverage:
- Done: 14 subfeatures (articles, categories/subcategories, statuses, approvals, versioning, comments, ratings, FAQ, template responses, tags, visibility, metadata/SEO, attachments, related linking)
- Partial: 3 (analytics dashboards, portal exposure, export tooling)
- Missing: 0 structural pieces

What works now
- Articles with status (draft/published/archived), visibility (internal/public), metadata (title/description/keywords), review due dates, and feature flags (`app/Models/KnowledgeArticle.php`).
- Versioning, approvals, comments, ratings, relations, and tags via dedicated models/resources (`KnowledgeArticleVersion.php`, `KnowledgeArticleApproval.php`, `KnowledgeArticleComment.php`, `KnowledgeArticleRating.php`, `KnowledgeArticleRelation.php`, `KnowledgeTag.php`).
- Categories/subcategories, FAQ entries, and template responses implemented with Filament resources.
- Attachments via media library; SEO fields captured; related-article linking; allow/disallow comments/ratings flags.

Gaps / partials
- Analytics beyond view/helpful counters are not surfaced; no dashboards.
- Customer portal exposure is not built; visibility flag is stored for future.
- Export of articles/templates is not implemented.

Source: docs/suitecrm-features.md (Knowledge Base)
