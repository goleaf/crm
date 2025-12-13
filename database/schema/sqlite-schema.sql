CREATE TABLE IF NOT EXISTS "migrations"(
  "id" integer primary key autoincrement not null,
  "migration" varchar not null,
  "batch" integer not null
);
CREATE TABLE IF NOT EXISTS "users"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar,
  "remember_token" varchar,
  "current_team_id" integer,
  "profile_photo_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "two_factor_secret" text,
  "two_factor_recovery_codes" text,
  "two_factor_confirmed_at" datetime,
  "timezone" varchar
);
CREATE UNIQUE INDEX "users_email_unique" on "users"("email");
CREATE TABLE IF NOT EXISTS "password_reset_tokens"(
  "email" varchar not null,
  "token" varchar not null,
  "created_at" datetime,
  primary key("email")
);
CREATE TABLE IF NOT EXISTS "sessions"(
  "id" varchar not null,
  "user_id" integer,
  "ip_address" varchar,
  "user_agent" text,
  "payload" text not null,
  "last_activity" integer not null,
  primary key("id")
);
CREATE INDEX "sessions_user_id_index" on "sessions"("user_id");
CREATE INDEX "sessions_last_activity_index" on "sessions"("last_activity");
CREATE TABLE IF NOT EXISTS "cache"(
  "key" varchar not null,
  "value" text not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "cache_locks"(
  "key" varchar not null,
  "owner" varchar not null,
  "expiration" integer not null,
  primary key("key")
);
CREATE TABLE IF NOT EXISTS "jobs"(
  "id" integer primary key autoincrement not null,
  "queue" varchar not null,
  "payload" text not null,
  "attempts" integer not null,
  "reserved_at" integer,
  "available_at" integer not null,
  "created_at" integer not null
);
CREATE INDEX "jobs_queue_index" on "jobs"("queue");
CREATE TABLE IF NOT EXISTS "job_batches"(
  "id" varchar not null,
  "name" varchar not null,
  "total_jobs" integer not null,
  "pending_jobs" integer not null,
  "failed_jobs" integer not null,
  "failed_job_ids" text not null,
  "options" text,
  "cancelled_at" integer,
  "created_at" integer not null,
  "finished_at" integer,
  primary key("id")
);
CREATE TABLE IF NOT EXISTS "failed_jobs"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "connection" text not null,
  "queue" text not null,
  "payload" text not null,
  "exception" text not null,
  "failed_at" datetime not null default CURRENT_TIMESTAMP
);
CREATE UNIQUE INDEX "failed_jobs_uuid_unique" on "failed_jobs"("uuid");
CREATE TABLE IF NOT EXISTS "countries"(
  "id" integer primary key autoincrement not null,
  "iso2" varchar not null,
  "name" varchar not null,
  "status" integer not null default '1',
  "phone_code" varchar not null,
  "iso3" varchar not null,
  "region" varchar not null,
  "subregion" varchar not null
);
CREATE TABLE IF NOT EXISTS "cities"(
  "id" integer primary key autoincrement not null,
  "country_id" integer not null,
  "state_id" integer not null,
  "name" varchar not null,
  "country_code" varchar not null
);
CREATE TABLE IF NOT EXISTS "timezones"(
  "id" integer primary key autoincrement not null,
  "country_id" integer not null,
  "name" varchar not null
);
CREATE TABLE IF NOT EXISTS "states"(
  "id" integer primary key autoincrement not null,
  "country_id" integer not null,
  "name" varchar not null,
  "country_code" varchar
);
CREATE TABLE IF NOT EXISTS "currencies"(
  "id" integer primary key autoincrement not null,
  "country_id" integer not null,
  "name" varchar not null,
  "code" varchar not null,
  "precision" integer not null default '2',
  "symbol" varchar not null,
  "symbol_native" varchar not null,
  "symbol_first" integer not null default '1',
  "decimal_mark" varchar not null default '.',
  "thousands_separator" varchar not null default ','
);
CREATE TABLE IF NOT EXISTS "languages"(
  "id" integer primary key autoincrement not null,
  "code" varchar not null,
  "name" varchar not null,
  "name_native" varchar not null,
  "dir" varchar not null
);
CREATE TABLE IF NOT EXISTS "schedules"(
  "id" integer primary key autoincrement not null,
  "schedulable_type" varchar not null,
  "schedulable_id" integer not null,
  "name" varchar,
  "description" text,
  "start_date" date not null,
  "end_date" date,
  "is_recurring" tinyint(1) not null default '0',
  "frequency" varchar,
  "frequency_config" text,
  "metadata" text,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "schedule_type" varchar check("schedule_type" in('availability', 'appointment', 'blocked', 'custom')) not null default 'custom'
);
CREATE INDEX "schedules_schedulable_type_schedulable_id_index" on "schedules"(
  "schedulable_type",
  "schedulable_id"
);
CREATE INDEX "schedules_schedulable_index" on "schedules"(
  "schedulable_type",
  "schedulable_id"
);
CREATE INDEX "schedules_date_range_index" on "schedules"(
  "start_date",
  "end_date"
);
CREATE INDEX "schedules_is_active_index" on "schedules"("is_active");
CREATE INDEX "schedules_is_recurring_index" on "schedules"("is_recurring");
CREATE INDEX "schedules_frequency_index" on "schedules"("frequency");
CREATE TABLE IF NOT EXISTS "schedule_periods"(
  "id" integer primary key autoincrement not null,
  "schedule_id" integer not null,
  "date" date not null,
  "start_time" time not null,
  "end_time" time not null,
  "is_available" tinyint(1) not null default '1',
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("schedule_id") references "schedules"("id") on delete cascade
);
CREATE INDEX "schedule_periods_schedule_date_index" on "schedule_periods"(
  "schedule_id",
  "date"
);
CREATE INDEX "schedule_periods_time_range_index" on "schedule_periods"(
  "date",
  "start_time",
  "end_time"
);
CREATE INDEX "schedule_periods_is_available_index" on "schedule_periods"(
  "is_available"
);
CREATE INDEX "schedules_type_index" on "schedules"("schedule_type");
CREATE INDEX "schedules_schedulable_type_index" on "schedules"(
  "schedulable_type",
  "schedulable_id",
  "schedule_type"
);
CREATE TABLE IF NOT EXISTS "ocr_templates"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "document_type" varchar not null,
  "field_definitions" text,
  "is_active" tinyint(1) not null default '1',
  "usage_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE INDEX "ocr_templates_team_id_document_type_index" on "ocr_templates"(
  "team_id",
  "document_type"
);
CREATE INDEX "ocr_templates_is_active_index" on "ocr_templates"("is_active");
CREATE TABLE IF NOT EXISTS "ocr_template_fields"(
  "id" integer primary key autoincrement not null,
  "template_id" integer not null,
  "field_name" varchar not null,
  "field_type" varchar not null,
  "extraction_pattern" text,
  "required" tinyint(1) not null default '0',
  "validation_rules" text,
  "description" text,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("template_id") references "ocr_templates"("id") on delete cascade
);
CREATE INDEX "ocr_template_fields_template_id_index" on "ocr_template_fields"(
  "template_id"
);
CREATE INDEX "ocr_template_fields_sort_order_index" on "ocr_template_fields"(
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "ocr_documents"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "template_id" integer,
  "user_id" integer not null,
  "file_path" varchar not null,
  "original_filename" varchar not null,
  "mime_type" varchar not null,
  "file_size" integer not null,
  "status" varchar not null default 'pending',
  "extracted_data" text,
  "raw_text" text,
  "confidence_score" numeric,
  "processing_time" numeric,
  "validation_errors" text,
  "error_message" text,
  "processed_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("template_id") references "ocr_templates"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "ocr_documents_team_id_status_index" on "ocr_documents"(
  "team_id",
  "status"
);
CREATE INDEX "ocr_documents_template_id_index" on "ocr_documents"(
  "template_id"
);
CREATE INDEX "ocr_documents_user_id_index" on "ocr_documents"("user_id");
CREATE INDEX "ocr_documents_status_index" on "ocr_documents"("status");
CREATE INDEX "ocr_documents_processed_at_index" on "ocr_documents"(
  "processed_at"
);
CREATE TABLE IF NOT EXISTS "teams"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "name" varchar not null,
  "personal_team" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "teams_user_id_index" on "teams"("user_id");
CREATE TABLE IF NOT EXISTS "team_user"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "user_id" integer not null,
  "role" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "team_user_team_id_user_id_unique" on "team_user"(
  "team_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "team_invitations"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "email" varchar not null,
  "role" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "team_invitations_team_id_email_unique" on "team_invitations"(
  "team_id",
  "email"
);
CREATE TABLE IF NOT EXISTS "reactions"(
  "id" integer primary key autoincrement not null,
  "user_id" integer,
  "reactable_type" varchar not null,
  "reactable_id" integer not null,
  "type" varchar not null,
  "ip" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "reactions_reactable_type_reactable_id_index" on "reactions"(
  "reactable_type",
  "reactable_id"
);
CREATE UNIQUE INDEX "reaction_user_name_per_ip" on "reactions"(
  "user_id",
  "reactable_type",
  "reactable_id",
  "ip"
);
CREATE TABLE IF NOT EXISTS "task_user"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "personal_access_tokens"(
  "id" integer primary key autoincrement not null,
  "tokenable_type" varchar not null,
  "tokenable_id" integer not null,
  "name" varchar not null,
  "token" varchar not null,
  "abilities" text,
  "last_used_at" datetime,
  "expires_at" datetime,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" on "personal_access_tokens"(
  "tokenable_type",
  "tokenable_id"
);
CREATE UNIQUE INDEX "personal_access_tokens_token_unique" on "personal_access_tokens"(
  "token"
);
CREATE TABLE IF NOT EXISTS "user_social_accounts"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "provider_name" varchar,
  "provider_id" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade on update cascade
);
CREATE UNIQUE INDEX "user_social_accounts_provider_name_provider_id_unique" on "user_social_accounts"(
  "provider_name",
  "provider_id"
);
CREATE TABLE IF NOT EXISTS "notifications"(
  "id" varchar not null,
  "type" varchar not null,
  "notifiable_type" varchar not null,
  "notifiable_id" integer not null,
  "data" text not null,
  "read_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  primary key("id")
);
CREATE INDEX "notifications_notifiable_type_notifiable_id_index" on "notifications"(
  "notifiable_type",
  "notifiable_id"
);
CREATE TABLE IF NOT EXISTS "model_meta"(
  "id" integer primary key autoincrement not null,
  "metable_type" varchar not null,
  "metable_id" integer not null,
  "type" varchar not null default 'null',
  "key" varchar not null,
  "value" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "model_meta_metable_type_metable_id_index" on "model_meta"(
  "metable_type",
  "metable_id"
);
CREATE UNIQUE INDEX "model_meta_unique" on "model_meta"(
  "metable_type",
  "metable_id",
  "key"
);
CREATE INDEX "model_meta_type_index" on "model_meta"("type");
CREATE INDEX "model_meta_key_index" on "model_meta"("key");
CREATE TABLE IF NOT EXISTS "unsplash_assets"(
  "id" integer primary key autoincrement not null,
  "unsplash_id" varchar not null,
  "slug" varchar,
  "description" text,
  "alt_description" text,
  "urls" text,
  "links" text,
  "width" integer,
  "height" integer,
  "color" varchar,
  "likes" integer not null default '0',
  "liked_by_user" tinyint(1) not null default '0',
  "photographer_name" varchar,
  "photographer_username" varchar,
  "photographer_url" varchar,
  "download_location" varchar,
  "local_path" varchar,
  "downloaded_at" datetime,
  "exif" text,
  "location" text,
  "tags" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime
);
CREATE INDEX "unsplash_assets_photographer_username_created_at_index" on "unsplash_assets"(
  "photographer_username",
  "created_at"
);
CREATE INDEX "unsplash_assets_downloaded_at_index" on "unsplash_assets"(
  "downloaded_at"
);
CREATE UNIQUE INDEX "unsplash_assets_unsplash_id_unique" on "unsplash_assets"(
  "unsplash_id"
);
CREATE TABLE IF NOT EXISTS "unsplashables"(
  "id" integer primary key autoincrement not null,
  "unsplash_asset_id" integer not null,
  "unsplashable_type" varchar not null,
  "unsplashable_id" integer not null,
  "collection" varchar,
  "order" integer not null default '0',
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("unsplash_asset_id") references "unsplash_assets"("id") on delete cascade
);
CREATE INDEX "unsplashables_unsplashable_type_unsplashable_id_index" on "unsplashables"(
  "unsplashable_type",
  "unsplashable_id"
);
CREATE UNIQUE INDEX "unsplashables_unique" on "unsplashables"(
  "unsplash_asset_id",
  "unsplashable_type",
  "unsplashable_id",
  "collection"
);
CREATE INDEX "unsplashables_unsplashable_type_unsplashable_id_order_index" on "unsplashables"(
  "unsplashable_type",
  "unsplashable_id",
  "order"
);
CREATE INDEX "unsplashables_collection_index" on "unsplashables"("collection");
CREATE TABLE IF NOT EXISTS "custom_field_sections"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer,
  "width" varchar,
  "code" varchar not null,
  "name" varchar not null,
  "type" varchar not null,
  "entity_type" varchar not null,
  "sort_order" integer,
  "description" varchar,
  "active" tinyint(1) not null default '1',
  "system_defined" tinyint(1) not null default '0',
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "custom_field_sections_entity_type_code_tenant_id_unique" on "custom_field_sections"(
  "entity_type",
  "code",
  "tenant_id"
);
CREATE INDEX "custom_field_sections_tenant_entity_active_idx" on "custom_field_sections"(
  "tenant_id",
  "entity_type",
  "active"
);
CREATE INDEX "custom_field_sections_tenant_id_index" on "custom_field_sections"(
  "tenant_id"
);
CREATE TABLE IF NOT EXISTS "custom_fields"(
  "id" integer primary key autoincrement not null,
  "custom_field_section_id" integer,
  "width" varchar,
  "tenant_id" integer,
  "code" varchar not null,
  "name" varchar not null,
  "type" varchar not null,
  "lookup_type" varchar,
  "entity_type" varchar not null,
  "sort_order" integer,
  "validation_rules" text,
  "active" tinyint(1) not null default '1',
  "system_defined" tinyint(1) not null default '0',
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "custom_fields_code_entity_type_tenant_id_unique" on "custom_fields"(
  "code",
  "entity_type",
  "tenant_id"
);
CREATE INDEX "custom_fields_tenant_entity_active_idx" on "custom_fields"(
  "tenant_id",
  "entity_type",
  "active"
);
CREATE INDEX "custom_fields_tenant_id_index" on "custom_fields"("tenant_id");
CREATE TABLE IF NOT EXISTS "custom_field_options"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer,
  "custom_field_id" integer not null,
  "name" varchar,
  "sort_order" integer,
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("custom_field_id") references "custom_fields"("id") on delete cascade
);
CREATE UNIQUE INDEX "custom_field_options_custom_field_id_name_tenant_id_unique" on "custom_field_options"(
  "custom_field_id",
  "name",
  "tenant_id"
);
CREATE INDEX "custom_field_options_tenant_id_index" on "custom_field_options"(
  "tenant_id"
);
CREATE TABLE IF NOT EXISTS "custom_field_values"(
  "id" integer primary key autoincrement not null,
  "tenant_id" integer,
  "entity_type" varchar not null,
  "entity_id" integer not null,
  "custom_field_id" integer not null,
  "string_value" text,
  "text_value" text,
  "boolean_value" tinyint(1),
  "integer_value" integer,
  "float_value" double,
  "date_value" date,
  "datetime_value" datetime,
  "json_value" text,
  foreign key("custom_field_id") references "custom_fields"("id") on delete cascade
);
CREATE INDEX "custom_field_values_entity_type_entity_id_index" on "custom_field_values"(
  "entity_type",
  "entity_id"
);
CREATE UNIQUE INDEX "custom_field_values_entity_type_unique" on "custom_field_values"(
  "entity_type",
  "entity_id",
  "custom_field_id",
  "tenant_id"
);
CREATE INDEX "custom_field_values_tenant_entity_idx" on "custom_field_values"(
  "tenant_id",
  "entity_type",
  "entity_id"
);
CREATE INDEX "custom_field_values_entity_id_custom_field_id_index" on "custom_field_values"(
  "entity_id",
  "custom_field_id"
);
CREATE INDEX "custom_field_values_tenant_id_index" on "custom_field_values"(
  "tenant_id"
);
CREATE TABLE IF NOT EXISTS "taskables"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "taskable_type" varchar not null,
  "taskable_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "taskables_taskable_type_taskable_id_index" on "taskables"(
  "taskable_type",
  "taskable_id"
);
CREATE TABLE IF NOT EXISTS "noteables"(
  "id" integer primary key autoincrement not null,
  "note_id" integer not null,
  "noteable_type" varchar not null,
  "noteable_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "noteables_noteable_type_noteable_id_index" on "noteables"(
  "noteable_type",
  "noteable_id"
);
CREATE TABLE IF NOT EXISTS "media"(
  "id" integer primary key autoincrement not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  "uuid" varchar,
  "collection_name" varchar not null,
  "name" varchar not null,
  "file_name" varchar not null,
  "mime_type" varchar,
  "disk" varchar not null,
  "conversions_disk" varchar,
  "size" integer not null,
  "manipulations" text not null,
  "custom_properties" text not null,
  "generated_conversions" text not null,
  "responsive_images" text not null,
  "order_column" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "team_id" integer
);
CREATE INDEX "media_model_type_model_id_index" on "media"(
  "model_type",
  "model_id"
);
CREATE UNIQUE INDEX "media_uuid_unique" on "media"("uuid");
CREATE INDEX "media_order_column_index" on "media"("order_column");
CREATE TABLE IF NOT EXISTS "imports"(
  "id" integer primary key autoincrement not null,
  "team_id" integer,
  "completed_at" datetime,
  "file_name" varchar not null,
  "file_path" varchar not null,
  "importer" varchar not null,
  "processed_rows" integer not null default '0',
  "total_rows" integer not null,
  "successful_rows" integer not null default '0',
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "exports"(
  "id" integer primary key autoincrement not null,
  "team_id" integer,
  "completed_at" datetime,
  "file_disk" varchar not null,
  "file_name" varchar,
  "exporter" varchar not null,
  "processed_rows" integer not null default '0',
  "total_rows" integer not null,
  "successful_rows" integer not null default '0',
  "user_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "failed_import_rows"(
  "id" integer primary key autoincrement not null,
  "team_id" integer,
  "data" text not null,
  "import_id" integer not null,
  "validation_error" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("import_id") references "imports"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "taxonomies"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "slug" varchar not null,
  "type" varchar not null,
  "description" text,
  "parent_id" integer,
  "sort_order" integer not null default '0',
  "lft" integer,
  "rgt" integer,
  "depth" integer,
  "meta" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("parent_id") references "taxonomies"("id")
);
CREATE UNIQUE INDEX "taxonomies_slug_type_team_id_deleted_at_unique" on "taxonomies"(
  "slug",
  "type",
  "team_id",
  "deleted_at"
);
CREATE INDEX "taxonomies_team_id_type_lft_rgt_index" on "taxonomies"(
  "team_id",
  "type",
  "lft",
  "rgt"
);
CREATE INDEX "taxonomies_team_id_type_slug_index" on "taxonomies"(
  "team_id",
  "type",
  "slug"
);
CREATE INDEX "taxonomies_type_index" on "taxonomies"("type");
CREATE INDEX "taxonomies_lft_index" on "taxonomies"("lft");
CREATE INDEX "taxonomies_rgt_index" on "taxonomies"("rgt");
CREATE INDEX "taxonomies_depth_index" on "taxonomies"("depth");
CREATE TABLE IF NOT EXISTS "taxonomables"(
  "id" integer primary key autoincrement not null,
  "taxonomy_id" integer not null,
  "taxonomable_type" varchar not null,
  "taxonomable_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("taxonomy_id") references "taxonomies"("id") on delete cascade
);
CREATE INDEX "taxonomables_taxonomable_type_taxonomable_id_index" on "taxonomables"(
  "taxonomable_type",
  "taxonomable_id"
);
CREATE UNIQUE INDEX "taxonomables_taxonomy_id_taxonomable_type_taxonomable_id_unique" on "taxonomables"(
  "taxonomy_id",
  "taxonomable_type",
  "taxonomable_id"
);
CREATE TABLE IF NOT EXISTS "system_administrators"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "email_verified_at" datetime,
  "password" varchar not null,
  "role" varchar not null,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "system_administrators_email_index" on "system_administrators"(
  "email"
);
CREATE UNIQUE INDEX "system_administrators_email_unique" on "system_administrators"(
  "email"
);
CREATE TABLE IF NOT EXISTS "task_categories"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "color" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "task_task_category"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "task_category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("task_category_id") references "task_categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "task_task_category_task_id_task_category_id_unique" on "task_task_category"(
  "task_id",
  "task_category_id"
);
CREATE TABLE IF NOT EXISTS "task_dependencies"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "depends_on_task_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("depends_on_task_id") references "tasks"("id") on delete cascade
);
CREATE UNIQUE INDEX "task_dependencies_task_id_depends_on_task_id_unique" on "task_dependencies"(
  "task_id",
  "depends_on_task_id"
);
CREATE TABLE IF NOT EXISTS "task_checklist_items"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "title" varchar not null,
  "is_completed" tinyint(1) not null default '0',
  "position" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "task_comments"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "user_id" integer,
  "parent_id" integer,
  "body" text not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null,
  foreign key("parent_id") references "task_comments"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "task_time_entries"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "user_id" integer,
  "started_at" datetime,
  "ended_at" datetime,
  "duration_minutes" integer,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  "is_billable" tinyint(1) not null default '0',
  "billing_rate" numeric,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "task_reminders"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "user_id" integer,
  "remind_at" datetime not null,
  "sent_at" datetime,
  "canceled_at" datetime,
  "channel" varchar not null default 'database',
  "status" varchar not null default 'pending',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "task_reminders_remind_at_index" on "task_reminders"("remind_at");
CREATE TABLE IF NOT EXISTS "task_recurrences"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "frequency" varchar not null,
  "interval" integer not null default '1',
  "days_of_week" text,
  "starts_on" date,
  "ends_on" date,
  "max_occurrences" integer,
  "timezone" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "task_delegations"(
  "id" integer primary key autoincrement not null,
  "task_id" integer not null,
  "from_user_id" integer,
  "to_user_id" integer,
  "status" varchar not null default 'pending',
  "delegated_at" datetime,
  "accepted_at" datetime,
  "declined_at" datetime,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_id") references "tasks"("id") on delete cascade,
  foreign key("from_user_id") references "users"("id") on delete set null,
  foreign key("to_user_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "accounts"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "slug" varchar not null,
  "team_id" integer not null,
  "parent_id" integer,
  "industry" varchar,
  "annual_revenue" numeric,
  "employee_count" integer,
  "currency" varchar not null default 'USD',
  "website" varchar,
  "social_links" text,
  "billing_address" text,
  "shipping_address" text,
  "custom_fields" text,
  "owner_id" integer not null,
  "assigned_to_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "type" varchar check("type" in('customer', 'prospect', 'partner', 'vendor', 'competitor', 'investor', 'reseller')) not null,
  "addresses" text,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("parent_id") references "accounts"("id"),
  foreign key("owner_id") references "users"("id"),
  foreign key("assigned_to_id") references "users"("id")
);
CREATE UNIQUE INDEX "accounts_slug_unique" on "accounts"("slug");
CREATE TABLE IF NOT EXISTS "account_merges"(
  "id" integer primary key autoincrement not null,
  "primary_company_id" integer not null,
  "duplicate_company_id" integer not null,
  "merged_by_user_id" integer,
  "field_selections" text,
  "transferred_relationships" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("primary_company_id") references "companies"("id") on delete cascade,
  foreign key("duplicate_company_id") references "companies"("id") on delete cascade,
  foreign key("merged_by_user_id") references "users"("id") on delete set null
);
CREATE INDEX "account_merges_primary_company_id_index" on "account_merges"(
  "primary_company_id"
);
CREATE INDEX "account_merges_duplicate_company_id_index" on "account_merges"(
  "duplicate_company_id"
);
CREATE INDEX "account_merges_merged_by_user_id_index" on "account_merges"(
  "merged_by_user_id"
);
CREATE TABLE IF NOT EXISTS "account_team_members"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "team_id" integer not null,
  "user_id" integer not null,
  "role" varchar not null default 'account_manager',
  "access_level" varchar not null default 'edit',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("company_id") references "companies"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "account_team_members_company_id_user_id_unique" on "account_team_members"(
  "company_id",
  "user_id"
);
CREATE INDEX "account_team_members_team_id_index" on "account_team_members"(
  "team_id"
);
CREATE TABLE IF NOT EXISTS "telescope_entries"(
  "sequence" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "batch_id" varchar not null,
  "family_hash" varchar,
  "should_display_on_index" tinyint(1) not null default '1',
  "type" varchar not null,
  "content" text not null,
  "created_at" datetime
);
CREATE UNIQUE INDEX "telescope_entries_uuid_unique" on "telescope_entries"(
  "uuid"
);
CREATE INDEX "telescope_entries_batch_id_index" on "telescope_entries"(
  "batch_id"
);
CREATE INDEX "telescope_entries_family_hash_index" on "telescope_entries"(
  "family_hash"
);
CREATE INDEX "telescope_entries_created_at_index" on "telescope_entries"(
  "created_at"
);
CREATE INDEX "telescope_entries_type_should_display_on_index_index" on "telescope_entries"(
  "type",
  "should_display_on_index"
);
CREATE TABLE IF NOT EXISTS "telescope_entries_tags"(
  "entry_uuid" varchar not null,
  "tag" varchar not null,
  foreign key("entry_uuid") references "telescope_entries"("uuid") on delete cascade,
  primary key("entry_uuid", "tag")
);
CREATE INDEX "telescope_entries_tags_tag_index" on "telescope_entries_tags"(
  "tag"
);
CREATE TABLE IF NOT EXISTS "telescope_monitoring"(
  "tag" varchar not null,
  primary key("tag")
);
CREATE TABLE IF NOT EXISTS "notables"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "note" text not null,
  "notable_type" varchar not null,
  "notable_id" integer not null,
  "creator_type" varchar,
  "creator_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE INDEX "notables_notable_type_notable_id_index" on "notables"(
  "notable_type",
  "notable_id"
);
CREATE INDEX "notables_creator_type_creator_id_index" on "notables"(
  "creator_type",
  "creator_id"
);
CREATE TABLE IF NOT EXISTS "features"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "scope" varchar not null,
  "value" text not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "features_name_scope_unique" on "features"(
  "name",
  "scope"
);
CREATE TABLE IF NOT EXISTS "feature_segments"(
  "id" integer primary key autoincrement not null,
  "feature" varchar not null,
  "scope" varchar not null,
  "values" text not null,
  "active" tinyint(1) not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "feature_segments_feature_scope_active_unique" on "feature_segments"(
  "feature",
  "scope",
  "active"
);
CREATE TABLE IF NOT EXISTS "opportunity_user"(
  "id" integer primary key autoincrement not null,
  "opportunity_id" integer not null,
  "user_id" integer not null,
  "role" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("opportunity_id") references "opportunities"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "opportunity_user_opportunity_id_user_id_unique" on "opportunity_user"(
  "opportunity_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "ltu_languages"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "code" varchar not null,
  "rtl" tinyint(1) not null default '0'
);
CREATE INDEX "ltu_languages_code_index" on "ltu_languages"("code");
CREATE TABLE IF NOT EXISTS "ltu_translations"(
  "id" integer primary key autoincrement not null,
  "language_id" integer not null,
  "source" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE TABLE IF NOT EXISTS "ltu_translation_files"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "extension" varchar not null,
  "is_root" tinyint(1) not null default '0'
);
CREATE TABLE IF NOT EXISTS "ltu_phrases"(
  "id" integer primary key autoincrement not null,
  "uuid" varchar not null,
  "translation_id" integer not null,
  "translation_file_id" integer not null,
  "phrase_id" integer,
  "key" varchar not null,
  "group" varchar not null,
  "value" text,
  "status" varchar not null default 'active',
  "parameters" text,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("translation_id") references "ltu_translations"("id") on delete cascade,
  foreign key("translation_file_id") references "ltu_translation_files"("id") on delete cascade,
  foreign key("phrase_id") references "ltu_phrases"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "ltu_contributors"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "email" varchar not null,
  "password" varchar not null,
  "avatar" varchar,
  "role" integer,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "ltu_contributors_email_unique" on "ltu_contributors"(
  "email"
);
CREATE TABLE IF NOT EXISTS "ltu_invites"(
  "id" integer primary key autoincrement not null,
  "email" varchar not null,
  "token" varchar not null,
  "role" integer not null default '2',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "ltu_invites_email_unique" on "ltu_invites"("email");
CREATE UNIQUE INDEX "ltu_invites_token_unique" on "ltu_invites"("token");
CREATE INDEX "idx_notables_notable" on "notables"(
  "notable_type",
  "notable_id"
);
CREATE TABLE IF NOT EXISTS "share_links"(
  "id" varchar not null,
  "resource" text not null,
  "token" varchar not null,
  "password" varchar,
  "expires_at" datetime,
  "max_clicks" integer,
  "click_count" integer not null default '0',
  "first_access_at" datetime,
  "last_access_at" datetime,
  "last_ip" varchar,
  "revoked_at" datetime,
  "metadata" text,
  "created_by" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("created_by") references "users"("id") on delete set null,
  primary key("id")
);
CREATE INDEX "share_links_expires_at_index" on "share_links"("expires_at");
CREATE INDEX "share_links_revoked_at_index" on "share_links"("revoked_at");
CREATE UNIQUE INDEX "share_links_token_unique" on "share_links"("token");
CREATE TABLE IF NOT EXISTS "db_config"(
  "id" integer primary key autoincrement not null,
  "group" varchar not null,
  "key" varchar not null,
  "settings" text,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "db_config_group_key_unique" on "db_config"(
  "group",
  "key"
);
CREATE TABLE IF NOT EXISTS "note_histories"(
  "id" integer primary key autoincrement not null,
  "note_id" integer not null,
  "team_id" integer not null,
  "user_id" integer,
  "title" varchar not null,
  "category" varchar,
  "visibility" varchar not null default 'internal',
  "body" text,
  "event" varchar not null default 'updated',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("note_id") references "notes"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "note_histories_note_id_created_at_index" on "note_histories"(
  "note_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "invoice_line_items"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default '1',
  "unit_price" numeric not null default '0',
  "tax_rate" numeric not null default '0',
  "line_total" numeric not null default '0',
  "tax_total" numeric not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_payments"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "team_id" integer not null,
  "amount" numeric not null,
  "currency_code" varchar not null default 'USD',
  "paid_at" datetime,
  "method" varchar,
  "reference" varchar,
  "status" varchar not null default 'completed',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_reminders"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "team_id" integer not null,
  "reminder_type" varchar not null,
  "remind_at" datetime not null,
  "sent_at" datetime,
  "channel" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "invoice_status_histories"(
  "id" integer primary key autoincrement not null,
  "invoice_id" integer not null,
  "team_id" integer not null,
  "from_status" varchar,
  "to_status" varchar not null,
  "changed_by" integer,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("invoice_id") references "invoices"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "tags"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "color" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "tags_team_id_name_unique" on "tags"("team_id", "name");
CREATE TABLE IF NOT EXISTS "taggables"(
  "id" integer primary key autoincrement not null,
  "tag_id" integer not null,
  "taggable_type" varchar not null,
  "taggable_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("tag_id") references "tags"("id") on delete cascade
);
CREATE INDEX "taggables_taggable_type_taggable_id_index" on "taggables"(
  "taggable_type",
  "taggable_id"
);
CREATE TABLE IF NOT EXISTS "activities"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "subject_type" varchar not null,
  "subject_id" integer not null,
  "causer_id" integer,
  "event" varchar not null,
  "changes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("causer_id") references "users"("id") on delete set null
);
CREATE INDEX "activities_subject_type_subject_id_index" on "activities"(
  "subject_type",
  "subject_id"
);
CREATE TABLE IF NOT EXISTS "deliveries"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "order_id" integer not null,
  "status" varchar not null default 'pending',
  "tracking_number" varchar,
  "shipped_at" datetime,
  "delivered_at" datetime,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("order_id") references "orders"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "vendors"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "company_id" integer,
  "name" varchar not null,
  "status" varchar not null default 'active',
  "contact_name" varchar,
  "contact_email" varchar,
  "contact_phone" varchar,
  "tax_id" varchar,
  "payment_terms" varchar,
  "shipping_terms" varchar,
  "ship_method" varchar,
  "preferred_currency" varchar,
  "rating" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("company_id") references "companies"("id") on delete set null
);
CREATE UNIQUE INDEX "vendors_team_id_name_unique" on "vendors"(
  "team_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "purchase_order_receipts"(
  "id" integer primary key autoincrement not null,
  "purchase_order_id" integer not null,
  "purchase_order_line_item_id" integer not null,
  "team_id" integer not null,
  "received_by_id" integer,
  "receipt_type" varchar not null default 'receipt',
  "quantity" numeric not null,
  "unit_cost" numeric not null default '0',
  "line_total" numeric not null default '0',
  "received_at" datetime,
  "reference" varchar,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("purchase_order_id") references "purchase_orders"("id") on delete cascade,
  foreign key("purchase_order_line_item_id") references "purchase_order_line_items"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("received_by_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "purchase_order_approvals"(
  "id" integer primary key autoincrement not null,
  "purchase_order_id" integer not null,
  "team_id" integer not null,
  "requested_by_id" integer,
  "approver_id" integer,
  "status" varchar not null default 'pending',
  "due_at" datetime,
  "decided_at" datetime,
  "approval_notes" text,
  "decision_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("purchase_order_id") references "purchase_orders"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("requested_by_id") references "users"("id") on delete set null,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "quote_line_items"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "team_id" integer not null,
  "product_id" integer,
  "sku" varchar,
  "name" varchar not null,
  "description" text,
  "tax_category" varchar,
  "quantity" numeric not null default '1',
  "unit_price" numeric not null default '0',
  "discount_type" varchar,
  "discount_value" numeric not null default '0',
  "tax_rate" numeric not null default '0',
  "line_total" numeric not null default '0',
  "tax_total" numeric not null default '0',
  "sort_order" integer not null default '0',
  "is_custom" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "quote_status_histories"(
  "id" integer primary key autoincrement not null,
  "quote_id" integer not null,
  "team_id" integer not null,
  "from_status" varchar,
  "to_status" varchar not null,
  "changed_by" integer,
  "note" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("quote_id") references "quotes"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("changed_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "order_line_items"(
  "id" integer primary key autoincrement not null,
  "order_id" integer not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default '1',
  "fulfilled_quantity" numeric not null default '0',
  "unit_price" numeric not null default '0',
  "tax_rate" numeric not null default '0',
  "line_total" numeric not null default '0',
  "tax_total" numeric not null default '0',
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("order_id") references "orders"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "purchase_order_line_items"(
  "id" integer primary key autoincrement not null,
  "purchase_order_id" integer not null,
  "order_line_item_id" integer,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "quantity" numeric not null default('1'),
  "received_quantity" numeric not null default('0'),
  "unit_cost" numeric not null default('0'),
  "tax_rate" numeric not null default('0'),
  "line_total" numeric not null default('0'),
  "tax_total" numeric not null default('0'),
  "expected_receipt_at" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("purchase_order_id") references purchase_orders("id") on delete cascade on update no action,
  foreign key("order_line_item_id") references "order_line_items"("id") on delete set null
);
CREATE INDEX "purchase_order_line_items_order_line_item_id_index" on "purchase_order_line_items"(
  "order_line_item_id"
);
CREATE TABLE IF NOT EXISTS "notification_preferences"(
  "id" integer primary key autoincrement not null,
  "user_id" integer not null,
  "in_app" tinyint(1) not null default '1',
  "email" tinyint(1) not null default '1',
  "realtime" tinyint(1) not null default '1',
  "activity_alerts" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "notification_preferences_user_id_unique" on "notification_preferences"(
  "user_id"
);
CREATE TABLE IF NOT EXISTS "document_versions"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "team_id" integer not null,
  "uploaded_by" integer,
  "version" integer not null,
  "file_path" varchar not null,
  "disk" varchar not null default 'public',
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("document_id") references "documents"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("uploaded_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "document_shares"(
  "id" integer primary key autoincrement not null,
  "document_id" integer not null,
  "team_id" integer not null,
  "user_id" integer not null,
  "permission" varchar not null default 'view',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("document_id") references "documents"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "document_shares_document_id_user_id_unique" on "document_shares"(
  "document_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "knowledge_article_versions"(
  "id" integer primary key autoincrement not null,
  "article_id" integer not null,
  "team_id" integer not null,
  "editor_id" integer,
  "approver_id" integer,
  "version" integer not null,
  "status" varchar not null default 'draft',
  "visibility" varchar not null default 'internal',
  "title" varchar not null,
  "slug" varchar not null,
  "summary" varchar,
  "content" text,
  "meta_title" varchar,
  "meta_description" text,
  "meta_keywords" text,
  "change_notes" text,
  "published_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "knowledge_article_versions_article_id_version_unique" on "knowledge_article_versions"(
  "article_id",
  "version"
);
CREATE INDEX "knowledge_article_versions_article_id_version_index" on "knowledge_article_versions"(
  "article_id",
  "version"
);
CREATE TABLE IF NOT EXISTS "knowledge_article_tag"(
  "id" integer primary key autoincrement not null,
  "article_id" integer not null,
  "tag_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("tag_id") references "knowledge_tags"("id") on delete cascade
);
CREATE UNIQUE INDEX "knowledge_article_tag_article_id_tag_id_unique" on "knowledge_article_tag"(
  "article_id",
  "tag_id"
);
CREATE TABLE IF NOT EXISTS "knowledge_article_approvals"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "article_id" integer not null,
  "requested_by_id" integer,
  "approver_id" integer,
  "status" varchar not null default 'pending',
  "due_at" datetime,
  "decided_at" datetime,
  "decision_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("requested_by_id") references "users"("id") on delete set null,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "knowledge_article_comments"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "article_id" integer not null,
  "author_id" integer,
  "parent_id" integer,
  "body" text not null,
  "status" varchar not null default 'pending',
  "is_internal" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("author_id") references "users"("id") on delete set null,
  foreign key("parent_id") references "knowledge_article_comments"("id") on delete set null
);
CREATE INDEX "knowledge_article_comments_article_id_status_index" on "knowledge_article_comments"(
  "article_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "knowledge_article_ratings"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "article_id" integer not null,
  "user_id" integer,
  "rating" integer not null,
  "feedback" text,
  "context" varchar not null default 'web',
  "ip_address" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "knowledge_article_ratings_article_id_user_id_unique" on "knowledge_article_ratings"(
  "article_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "knowledge_article_relations"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "article_id" integer not null,
  "related_article_id" integer not null,
  "relation_type" varchar not null default 'related',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("article_id") references "knowledge_articles"("id") on delete cascade,
  foreign key("related_article_id") references "knowledge_articles"("id") on delete cascade
);
CREATE UNIQUE INDEX "knowledge_article_relations_article_id_related_article_id_unique" on "knowledge_article_relations"(
  "article_id",
  "related_article_id"
);
CREATE TABLE IF NOT EXISTS "process_definitions"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "status" varchar not null default 'draft',
  "version" integer not null default '1',
  "steps" text,
  "business_rules" text,
  "event_triggers" text,
  "sla_config" text,
  "escalation_rules" text,
  "metadata" text,
  "documentation" text,
  "template_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "trigger_type" varchar,
  "target_model" varchar,
  "conditions" text,
  "condition_logic" varchar not null default 'and',
  "allow_repeated_runs" tinyint(1) not null default '0',
  "max_runs_per_record" integer,
  "schedule_config" text,
  "test_mode" tinyint(1) not null default '0',
  "enable_logging" tinyint(1) not null default '1',
  "log_level" varchar not null default 'info',
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("creator_id") references "users"("id") on delete set null,
  foreign key("template_id") references "process_definitions"("id") on delete set null
);
CREATE INDEX "process_definitions_team_id_status_index" on "process_definitions"(
  "team_id",
  "status"
);
CREATE INDEX "process_definitions_slug_version_index" on "process_definitions"(
  "slug",
  "version"
);
CREATE UNIQUE INDEX "process_definitions_slug_unique" on "process_definitions"(
  "slug"
);
CREATE TABLE IF NOT EXISTS "process_executions"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "process_definition_id" integer not null,
  "initiated_by_id" integer,
  "status" varchar not null default 'pending',
  "process_version" integer not null,
  "context_data" text,
  "execution_state" text,
  "started_at" datetime,
  "completed_at" datetime,
  "sla_due_at" datetime,
  "error_message" text,
  "rollback_data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("process_definition_id") references "process_definitions"("id") on delete cascade,
  foreign key("initiated_by_id") references "users"("id") on delete set null
);
CREATE INDEX "process_executions_team_id_status_index" on "process_executions"(
  "team_id",
  "status"
);
CREATE INDEX "process_executions_process_definition_id_status_index" on "process_executions"(
  "process_definition_id",
  "status"
);
CREATE INDEX "process_executions_sla_due_at_index" on "process_executions"(
  "sla_due_at"
);
CREATE TABLE IF NOT EXISTS "process_execution_steps"(
  "id" integer primary key autoincrement not null,
  "execution_id" integer not null,
  "team_id" integer not null,
  "assigned_to_id" integer,
  "step_key" varchar not null,
  "step_name" varchar not null,
  "step_order" integer not null,
  "status" varchar not null default 'pending',
  "step_config" text,
  "input_data" text,
  "output_data" text,
  "started_at" datetime,
  "completed_at" datetime,
  "due_at" datetime,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("execution_id") references "process_executions"("id") on delete cascade,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("assigned_to_id") references "users"("id") on delete set null
);
CREATE INDEX "process_execution_steps_execution_id_step_order_index" on "process_execution_steps"(
  "execution_id",
  "step_order"
);
CREATE INDEX "process_execution_steps_status_due_at_index" on "process_execution_steps"(
  "status",
  "due_at"
);
CREATE TABLE IF NOT EXISTS "process_approvals"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "execution_id" integer not null,
  "execution_step_id" integer,
  "requested_by_id" integer,
  "approver_id" integer,
  "status" varchar not null default 'pending',
  "approval_notes" text,
  "decision_notes" text,
  "due_at" datetime,
  "decided_at" datetime,
  "escalated_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("execution_id") references "process_executions"("id") on delete cascade,
  foreign key("execution_step_id") references "process_execution_steps"("id") on delete set null,
  foreign key("requested_by_id") references "users"("id") on delete set null,
  foreign key("approver_id") references "users"("id") on delete set null
);
CREATE INDEX "process_approvals_execution_id_status_index" on "process_approvals"(
  "execution_id",
  "status"
);
CREATE INDEX "process_approvals_approver_id_status_index" on "process_approvals"(
  "approver_id",
  "status"
);
CREATE INDEX "process_approvals_due_at_index" on "process_approvals"("due_at");
CREATE TABLE IF NOT EXISTS "process_escalations"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "execution_id" integer not null,
  "execution_step_id" integer,
  "escalated_to_id" integer,
  "escalated_by_id" integer,
  "escalation_reason" varchar not null,
  "escalation_notes" text,
  "escalation_config" text,
  "is_resolved" tinyint(1) not null default '0',
  "resolved_at" datetime,
  "resolution_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("execution_id") references "process_executions"("id") on delete cascade,
  foreign key("execution_step_id") references "process_execution_steps"("id") on delete set null,
  foreign key("escalated_to_id") references "users"("id") on delete set null,
  foreign key("escalated_by_id") references "users"("id") on delete set null
);
CREATE INDEX "process_escalations_execution_id_is_resolved_index" on "process_escalations"(
  "execution_id",
  "is_resolved"
);
CREATE INDEX "process_escalations_escalated_to_id_index" on "process_escalations"(
  "escalated_to_id"
);
CREATE TABLE IF NOT EXISTS "process_audit_logs"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "execution_id" integer not null,
  "execution_step_id" integer,
  "user_id" integer,
  "event_type" varchar not null,
  "event_description" text,
  "event_data" text,
  "state_before" text,
  "state_after" text,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("execution_id") references "process_executions"("id") on delete cascade,
  foreign key("execution_step_id") references "process_execution_steps"("id") on delete set null,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "process_audit_logs_execution_id_event_type_index" on "process_audit_logs"(
  "execution_id",
  "event_type"
);
CREATE INDEX "process_audit_logs_team_id_created_at_index" on "process_audit_logs"(
  "team_id",
  "created_at"
);
CREATE TABLE IF NOT EXISTS "process_analytics"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "process_definition_id" integer not null,
  "metric_date" date not null,
  "executions_started" integer not null default '0',
  "executions_completed" integer not null default '0',
  "executions_failed" integer not null default '0',
  "sla_breaches" integer not null default '0',
  "escalations" integer not null default '0',
  "avg_completion_time_seconds" integer,
  "min_completion_time_seconds" integer,
  "max_completion_time_seconds" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("process_definition_id") references "process_definitions"("id") on delete cascade
);
CREATE UNIQUE INDEX "process_analytics_process_definition_id_metric_date_unique" on "process_analytics"(
  "process_definition_id",
  "metric_date"
);
CREATE INDEX "process_analytics_team_id_metric_date_index" on "process_analytics"(
  "team_id",
  "metric_date"
);
CREATE TABLE IF NOT EXISTS "extensions"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "type" varchar not null,
  "status" varchar not null default 'inactive',
  "version" varchar not null default '1.0.0',
  "priority" integer not null default '100',
  "target_model" varchar,
  "target_event" varchar,
  "handler_class" varchar not null,
  "handler_method" varchar not null default 'handle',
  "configuration" text,
  "permissions" text,
  "metadata" text,
  "execution_count" integer not null default '0',
  "failure_count" integer not null default '0',
  "last_executed_at" datetime,
  "last_error" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("creator_id") references "users"("id") on delete set null
);
CREATE INDEX "extensions_team_id_type_status_index" on "extensions"(
  "team_id",
  "type",
  "status"
);
CREATE INDEX "extensions_target_model_target_event_index" on "extensions"(
  "target_model",
  "target_event"
);
CREATE INDEX "extensions_priority_index" on "extensions"("priority");
CREATE UNIQUE INDEX "extensions_slug_unique" on "extensions"("slug");
CREATE TABLE IF NOT EXISTS "extension_executions"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "extension_id" integer not null,
  "user_id" integer,
  "status" varchar not null,
  "input_data" text,
  "output_data" text,
  "error_message" text,
  "execution_time_ms" integer,
  "ip_address" varchar,
  "user_agent" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("extension_id") references "extensions"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "extension_executions_extension_id_status_index" on "extension_executions"(
  "extension_id",
  "status"
);
CREATE INDEX "extension_executions_created_at_index" on "extension_executions"(
  "created_at"
);
CREATE TABLE IF NOT EXISTS "pdf_generations"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "pdf_template_id" integer not null,
  "user_id" integer,
  "entity_type" varchar not null,
  "entity_id" integer not null,
  "file_path" varchar not null,
  "file_name" varchar not null,
  "file_size" integer,
  "page_count" integer not null default '1',
  "merge_data" text,
  "generation_options" text,
  "has_watermark" tinyint(1) not null default '0',
  "is_encrypted" tinyint(1) not null default '0',
  "status" varchar not null default 'completed',
  "error_message" text,
  "generated_at" datetime not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("pdf_template_id") references "pdf_templates"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null
);
CREATE INDEX "pdf_generations_entity_type_entity_id_index" on "pdf_generations"(
  "entity_type",
  "entity_id"
);
CREATE INDEX "pdf_generations_team_id_generated_at_index" on "pdf_generations"(
  "team_id",
  "generated_at"
);
CREATE TABLE IF NOT EXISTS "territories"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "code" varchar not null,
  "type" varchar check("type" in('geographic', 'product', 'hybrid')) not null default 'geographic',
  "description" text,
  "parent_id" integer,
  "level" integer not null default '0',
  "path" varchar,
  "assignment_rules" text,
  "revenue_quota" numeric,
  "unit_quota" integer,
  "quota_period" varchar,
  "is_active" tinyint(1) not null default '1',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("parent_id") references "territories"("id") on delete set null
);
CREATE INDEX "territories_team_id_is_active_index" on "territories"(
  "team_id",
  "is_active"
);
CREATE INDEX "territories_parent_id_index" on "territories"("parent_id");
CREATE INDEX "territories_type_index" on "territories"("type");
CREATE UNIQUE INDEX "territories_code_unique" on "territories"("code");
CREATE TABLE IF NOT EXISTS "territory_assignments"(
  "id" integer primary key autoincrement not null,
  "territory_id" integer not null,
  "user_id" integer not null,
  "role" varchar check("role" in('owner', 'member', 'viewer')) not null default 'member',
  "is_primary" tinyint(1) not null default '0',
  "start_date" date,
  "end_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("territory_id") references "territories"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "territory_assignments_territory_id_user_id_unique" on "territory_assignments"(
  "territory_id",
  "user_id"
);
CREATE INDEX "territory_assignments_user_id_is_primary_index" on "territory_assignments"(
  "user_id",
  "is_primary"
);
CREATE TABLE IF NOT EXISTS "territory_records"(
  "id" integer primary key autoincrement not null,
  "territory_id" integer not null,
  "record_type" varchar not null,
  "record_id" integer not null,
  "is_primary" tinyint(1) not null default '1',
  "assigned_at" datetime not null default CURRENT_TIMESTAMP,
  "assignment_reason" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("territory_id") references "territories"("id") on delete cascade
);
CREATE INDEX "territory_records_record_type_record_id_index" on "territory_records"(
  "record_type",
  "record_id"
);
CREATE INDEX "territory_records_territory_id_is_primary_index" on "territory_records"(
  "territory_id",
  "is_primary"
);
CREATE TABLE IF NOT EXISTS "territory_transfers"(
  "id" integer primary key autoincrement not null,
  "from_territory_id" integer not null,
  "to_territory_id" integer not null,
  "record_type" varchar not null,
  "record_id" integer not null,
  "initiated_by" integer not null,
  "reason" text,
  "transferred_at" datetime not null default CURRENT_TIMESTAMP,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("from_territory_id") references "territories"("id") on delete cascade,
  foreign key("to_territory_id") references "territories"("id") on delete cascade,
  foreign key("initiated_by") references "users"("id")
);
CREATE INDEX "territory_transfers_record_type_record_id_index" on "territory_transfers"(
  "record_type",
  "record_id"
);
CREATE TABLE IF NOT EXISTS "territory_quotas"(
  "id" integer primary key autoincrement not null,
  "territory_id" integer not null,
  "period" varchar not null,
  "revenue_target" numeric,
  "unit_target" integer,
  "revenue_actual" numeric not null default '0',
  "unit_actual" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("territory_id") references "territories"("id") on delete cascade
);
CREATE UNIQUE INDEX "territory_quotas_territory_id_period_unique" on "territory_quotas"(
  "territory_id",
  "period"
);
CREATE TABLE IF NOT EXISTS "territory_overlaps"(
  "id" integer primary key autoincrement not null,
  "territory_a_id" integer not null,
  "territory_b_id" integer not null,
  "resolution_strategy" varchar check("resolution_strategy" in('split', 'priority', 'manual')) not null default 'manual',
  "priority_territory_id" integer,
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("territory_a_id") references "territories"("id") on delete cascade,
  foreign key("territory_b_id") references "territories"("id") on delete cascade
);
CREATE UNIQUE INDEX "territory_overlaps_territory_a_id_territory_b_id_unique" on "territory_overlaps"(
  "territory_a_id",
  "territory_b_id"
);
CREATE TABLE IF NOT EXISTS "email_programs"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "type" varchar not null default 'drip',
  "status" varchar not null default 'draft',
  "audience_filters" text,
  "estimated_audience_size" integer not null default '0',
  "scheduled_start_at" datetime,
  "scheduled_end_at" datetime,
  "started_at" datetime,
  "completed_at" datetime,
  "is_ab_test" tinyint(1) not null default '0',
  "ab_test_sample_size_percent" integer,
  "ab_test_winner_metric" varchar,
  "ab_test_winner_selected_at" datetime,
  "ab_test_winner_variant" varchar,
  "personalization_rules" text,
  "dynamic_content_blocks" text,
  "scoring_rules" text,
  "min_engagement_score" integer not null default '0',
  "throttle_rate_per_hour" integer,
  "send_time_optimization" text,
  "respect_quiet_hours" tinyint(1) not null default '1',
  "quiet_hours_start" time,
  "quiet_hours_end" time,
  "total_recipients" integer not null default '0',
  "total_sent" integer not null default '0',
  "total_delivered" integer not null default '0',
  "total_opened" integer not null default '0',
  "total_clicked" integer not null default '0',
  "total_bounced" integer not null default '0',
  "total_unsubscribed" integer not null default '0',
  "total_complained" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE INDEX "email_programs_team_id_status_index" on "email_programs"(
  "team_id",
  "status"
);
CREATE INDEX "email_programs_team_id_type_index" on "email_programs"(
  "team_id",
  "type"
);
CREATE INDEX "email_programs_scheduled_start_at_index" on "email_programs"(
  "scheduled_start_at"
);
CREATE TABLE IF NOT EXISTS "email_program_steps"(
  "id" integer primary key autoincrement not null,
  "email_program_id" integer not null,
  "step_order" integer not null default '0',
  "name" varchar not null,
  "description" text,
  "subject_line" varchar not null,
  "preview_text" varchar,
  "html_content" text,
  "plain_text_content" text,
  "from_name" varchar,
  "from_email" varchar,
  "reply_to_email" varchar,
  "variant_name" varchar,
  "is_control" tinyint(1) not null default '0',
  "delay_value" integer not null default '0',
  "delay_unit" varchar not null default 'days',
  "conditional_send_rules" text,
  "recipients_count" integer not null default '0',
  "sent_count" integer not null default '0',
  "delivered_count" integer not null default '0',
  "opened_count" integer not null default '0',
  "clicked_count" integer not null default '0',
  "bounced_count" integer not null default '0',
  "unsubscribed_count" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("email_program_id") references "email_programs"("id") on delete cascade
);
CREATE INDEX "email_program_steps_email_program_id_step_order_index" on "email_program_steps"(
  "email_program_id",
  "step_order"
);
CREATE INDEX "email_program_steps_email_program_id_variant_name_index" on "email_program_steps"(
  "email_program_id",
  "variant_name"
);
CREATE TABLE IF NOT EXISTS "email_program_recipients"(
  "id" integer primary key autoincrement not null,
  "email_program_id" integer not null,
  "email_program_step_id" integer,
  "email" varchar not null,
  "first_name" varchar,
  "last_name" varchar,
  "custom_fields" text,
  "recipient_type" varchar not null,
  "recipient_id" integer not null,
  "status" varchar not null default 'pending',
  "scheduled_send_at" datetime,
  "sent_at" datetime,
  "delivered_at" datetime,
  "opened_at" datetime,
  "clicked_at" datetime,
  "bounced_at" datetime,
  "unsubscribed_at" datetime,
  "open_count" integer not null default '0',
  "click_count" integer not null default '0',
  "engagement_score" integer not null default '0',
  "bounce_type" varchar,
  "bounce_reason" text,
  "error_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("email_program_id") references "email_programs"("id") on delete cascade,
  foreign key("email_program_step_id") references "email_program_steps"("id") on delete set null
);
CREATE INDEX "email_program_recipients_recipient_type_recipient_id_index" on "email_program_recipients"(
  "recipient_type",
  "recipient_id"
);
CREATE INDEX "email_program_recipients_email_program_id_status_index" on "email_program_recipients"(
  "email_program_id",
  "status"
);
CREATE INDEX "email_program_recipients_email_program_id_email_index" on "email_program_recipients"(
  "email_program_id",
  "email"
);
CREATE INDEX "email_program_recipients_scheduled_send_at_index" on "email_program_recipients"(
  "scheduled_send_at"
);
CREATE TABLE IF NOT EXISTS "email_program_unsubscribes"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "email" varchar not null,
  "email_program_id" integer,
  "reason" varchar,
  "feedback" text,
  "ip_address" varchar,
  "user_agent" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("email_program_id") references "email_programs"("id") on delete set null
);
CREATE UNIQUE INDEX "email_program_unsubscribes_team_id_email_unique" on "email_program_unsubscribes"(
  "team_id",
  "email"
);
CREATE INDEX "email_program_unsubscribes_email_index" on "email_program_unsubscribes"(
  "email"
);
CREATE TABLE IF NOT EXISTS "email_program_bounces"(
  "id" integer primary key autoincrement not null,
  "email_program_id" integer not null,
  "email_program_recipient_id" integer,
  "email" varchar not null,
  "bounce_type" varchar not null,
  "bounce_reason" text,
  "diagnostic_code" text,
  "raw_message" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("email_program_id") references "email_programs"("id") on delete cascade,
  foreign key("email_program_recipient_id") references "email_program_recipients"("id") on delete set null
);
CREATE INDEX "email_program_bounces_email_program_id_bounce_type_index" on "email_program_bounces"(
  "email_program_id",
  "bounce_type"
);
CREATE INDEX "email_program_bounces_email_bounce_type_index" on "email_program_bounces"(
  "email",
  "bounce_type"
);
CREATE INDEX "email_program_bounces_email_index" on "email_program_bounces"(
  "email"
);
CREATE TABLE IF NOT EXISTS "email_program_analytics"(
  "id" integer primary key autoincrement not null,
  "email_program_id" integer not null,
  "email_program_step_id" integer,
  "date" date not null,
  "sent_count" integer not null default '0',
  "delivered_count" integer not null default '0',
  "opened_count" integer not null default '0',
  "unique_opens" integer not null default '0',
  "clicked_count" integer not null default '0',
  "unique_clicks" integer not null default '0',
  "bounced_count" integer not null default '0',
  "unsubscribed_count" integer not null default '0',
  "complained_count" integer not null default '0',
  "delivery_rate" numeric not null default '0',
  "open_rate" numeric not null default '0',
  "click_rate" numeric not null default '0',
  "bounce_rate" numeric not null default '0',
  "unsubscribe_rate" numeric not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("email_program_id") references "email_programs"("id") on delete cascade,
  foreign key("email_program_step_id") references "email_program_steps"("id") on delete set null
);
CREATE UNIQUE INDEX "email_analytics_unique" on "email_program_analytics"(
  "email_program_id",
  "email_program_step_id",
  "date"
);
CREATE INDEX "email_program_analytics_email_program_id_date_index" on "email_program_analytics"(
  "email_program_id",
  "date"
);
CREATE TABLE IF NOT EXISTS "product_categories"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "parent_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "sort_order" integer not null default '0',
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("parent_id") references "product_categories"("id") on delete set null
);
CREATE UNIQUE INDEX "product_categories_team_id_slug_unique" on "product_categories"(
  "team_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "products"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "slug" varchar not null,
  "sku" varchar not null,
  "description" text,
  "price" numeric not null default '0',
  "currency_code" varchar not null default 'USD',
  "is_active" tinyint(1) not null default '1',
  "track_inventory" tinyint(1) not null default '0',
  "inventory_quantity" integer not null default '0',
  "custom_fields" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "reserved_quantity" integer not null default '0',
  "part_number" varchar,
  "manufacturer" varchar,
  "product_type" varchar not null default 'stocked',
  "status" varchar not null default 'active',
  "lifecycle_stage" varchar not null default 'released',
  "cost_price" numeric not null default '0',
  "price_effective_from" datetime,
  "price_effective_to" datetime,
  "is_bundle" tinyint(1) not null default '0',
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "products_team_id_slug_unique" on "products"(
  "team_id",
  "slug"
);
CREATE UNIQUE INDEX "products_team_id_sku_unique" on "products"(
  "team_id",
  "sku"
);
CREATE TABLE IF NOT EXISTS "category_product"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "product_category_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("product_category_id") references "product_categories"("id") on delete cascade
);
CREATE UNIQUE INDEX "category_product_product_id_product_category_id_unique" on "category_product"(
  "product_id",
  "product_category_id"
);
CREATE TABLE IF NOT EXISTS "product_attributes"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "slug" varchar not null,
  "data_type" varchar not null default 'text',
  "is_configurable" tinyint(1) not null default '0',
  "is_filterable" tinyint(1) not null default '0',
  "is_required" tinyint(1) not null default '0',
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "product_attributes_team_id_slug_unique" on "product_attributes"(
  "team_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "product_attribute_values"(
  "id" integer primary key autoincrement not null,
  "product_attribute_id" integer not null,
  "value" varchar not null,
  "code" varchar,
  "sort_order" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("product_attribute_id") references "product_attributes"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "product_attribute_assignments"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "product_attribute_id" integer not null,
  "product_attribute_value_id" integer,
  "custom_value" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("product_attribute_id") references "product_attributes"("id") on delete cascade,
  foreign key("product_attribute_value_id") references "product_attribute_values"("id") on delete set null
);
CREATE UNIQUE INDEX "product_attribute_assignment_unique" on "product_attribute_assignments"(
  "product_id",
  "product_attribute_id",
  "product_attribute_value_id"
);
CREATE TABLE IF NOT EXISTS "product_configurable_attributes"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "product_attribute_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("product_attribute_id") references "product_attributes"("id") on delete cascade
);
CREATE UNIQUE INDEX "product_configurable_attributes_unique" on "product_configurable_attributes"(
  "product_id",
  "product_attribute_id"
);
CREATE TABLE IF NOT EXISTS "product_variations"(
  "id" integer primary key autoincrement not null,
  "product_id" integer not null,
  "name" varchar,
  "sku" varchar not null,
  "price" numeric,
  "currency_code" varchar,
  "is_default" tinyint(1) not null default '0',
  "track_inventory" tinyint(1) not null default '0',
  "inventory_quantity" integer not null default '0',
  "reserved_quantity" integer not null default '0',
  "options" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("product_id") references "products"("id") on delete cascade
);
CREATE UNIQUE INDEX "product_variations_product_id_sku_unique" on "product_variations"(
  "product_id",
  "sku"
);
CREATE INDEX "products_team_active_index" on "products"(
  "team_id",
  "is_active"
);
CREATE INDEX "products_team_status_lifecycle_index" on "products"(
  "team_id",
  "status",
  "lifecycle_stage"
);
CREATE INDEX "products_track_inventory_index" on "products"("track_inventory");
CREATE INDEX "products_created_at_index" on "products"("created_at");
CREATE INDEX "products_updated_at_index" on "products"("updated_at");
CREATE INDEX "product_categories_parent_id_index" on "product_categories"(
  "parent_id"
);
CREATE INDEX "product_categories_team_parent_index" on "product_categories"(
  "team_id",
  "parent_id"
);
CREATE INDEX "product_attributes_configurable_index" on "product_attributes"(
  "is_configurable"
);
CREATE INDEX "product_attributes_filterable_index" on "product_attributes"(
  "is_filterable"
);
CREATE INDEX "product_attributes_data_type_index" on "product_attributes"(
  "data_type"
);
CREATE INDEX "product_variations_product_default_index" on "product_variations"(
  "product_id",
  "is_default"
);
CREATE INDEX "product_variations_track_inventory_index" on "product_variations"(
  "track_inventory"
);
CREATE INDEX "category_product_category_index" on "category_product"(
  "product_category_id"
);
CREATE INDEX "product_configurable_attributes_attribute_index" on "product_configurable_attributes"(
  "product_attribute_id"
);
CREATE INDEX "product_attribute_assignments_attribute_index" on "product_attribute_assignments"(
  "product_attribute_id"
);
CREATE INDEX "product_attribute_assignments_value_index" on "product_attribute_assignments"(
  "product_attribute_value_id"
);
CREATE INDEX "product_attribute_values_sort_order_index" on "product_attribute_values"(
  "sort_order"
);
CREATE INDEX "product_categories_sort_order_index" on "product_categories"(
  "sort_order"
);
CREATE TABLE IF NOT EXISTS "inventory_adjustments"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "adjustable_type" varchar not null,
  "adjustable_id" integer not null,
  "user_id" integer not null,
  "quantity_before" integer not null,
  "quantity_after" integer not null,
  "adjustment_quantity" integer not null,
  "reason" varchar not null,
  "notes" text,
  "reference_type" varchar,
  "reference_id" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "inventory_adjustments_adjustable_type_adjustable_id_index" on "inventory_adjustments"(
  "adjustable_type",
  "adjustable_id"
);
CREATE INDEX "inventory_adjustments_adjustable_index" on "inventory_adjustments"(
  "adjustable_type",
  "adjustable_id"
);
CREATE INDEX "inventory_adjustments_team_date_index" on "inventory_adjustments"(
  "team_id",
  "created_at"
);
CREATE INDEX "inventory_adjustments_user_index" on "inventory_adjustments"(
  "user_id"
);
CREATE INDEX "inventory_adjustments_reason_index" on "inventory_adjustments"(
  "reason"
);
CREATE INDEX "inventory_adjustments_reference_index" on "inventory_adjustments"(
  "reference_type",
  "reference_id"
);
CREATE INDEX "products_reserved_quantity_index" on "products"(
  "reserved_quantity"
);
CREATE INDEX "product_variations_reserved_quantity_index" on "product_variations"(
  "reserved_quantity"
);
CREATE TABLE IF NOT EXISTS "settings"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" text,
  "type" varchar not null default 'string',
  "group" varchar not null default 'general',
  "description" text,
  "is_public" tinyint(1) not null default '0',
  "is_encrypted" tinyint(1) not null default '0',
  "team_id" integer,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE INDEX "settings_group_key_index" on "settings"("group", "key");
CREATE INDEX "settings_team_id_index" on "settings"("team_id");
CREATE INDEX "settings_team_id_key_index" on "settings"("team_id", "key");
CREATE INDEX "settings_is_public_key_index" on "settings"("is_public", "key");
CREATE UNIQUE INDEX "settings_key_unique" on "settings"("key");
CREATE TABLE IF NOT EXISTS "saved_searches"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "user_id" integer not null,
  "name" varchar not null,
  "resource" varchar not null default 'global',
  "query" varchar,
  "filters" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE INDEX "saved_searches_team_id_resource_index" on "saved_searches"(
  "team_id",
  "resource"
);
CREATE UNIQUE INDEX "saved_searches_team_id_user_id_name_resource_unique" on "saved_searches"(
  "team_id",
  "user_id",
  "name",
  "resource"
);
CREATE UNIQUE INDEX "products_team_id_part_number_unique" on "products"(
  "team_id",
  "part_number"
);
CREATE INDEX "products_status_lifecycle_stage_index" on "products"(
  "status",
  "lifecycle_stage"
);
CREATE TABLE IF NOT EXISTS "product_price_tiers"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "product_id" integer not null,
  "min_quantity" integer not null default '1',
  "max_quantity" integer,
  "price" numeric not null,
  "currency_code" varchar not null default 'USD',
  "starts_at" datetime,
  "ends_at" datetime,
  "label" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("product_id") references "products"("id") on delete cascade
);
CREATE INDEX "product_price_tiers_product_id_min_quantity_starts_at_ends_at_index" on "product_price_tiers"(
  "product_id",
  "min_quantity",
  "starts_at",
  "ends_at"
);
CREATE TABLE IF NOT EXISTS "product_discount_rules"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "product_id" integer,
  "product_category_id" integer,
  "company_id" integer,
  "name" varchar not null,
  "scope" varchar not null default 'product',
  "discount_type" varchar not null,
  "discount_value" numeric not null,
  "min_quantity" integer not null default '1',
  "max_quantity" integer,
  "starts_at" datetime,
  "ends_at" datetime,
  "is_active" tinyint(1) not null default '1',
  "priority" integer not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("product_category_id") references "product_categories"("id") on delete cascade,
  foreign key("company_id") references "companies"("id") on delete set null
);
CREATE INDEX "product_discount_rules_product_id_product_category_id_company_id_index" on "product_discount_rules"(
  "product_id",
  "product_category_id",
  "company_id"
);
CREATE INDEX "product_discount_rules_is_active_starts_at_ends_at_index" on "product_discount_rules"(
  "is_active",
  "starts_at",
  "ends_at"
);
CREATE TABLE IF NOT EXISTS "product_relationships"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "product_id" integer not null,
  "related_product_id" integer not null,
  "relationship_type" varchar not null,
  "priority" integer not null default '0',
  "quantity" integer not null default '1',
  "price_override" numeric,
  "is_required" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("product_id") references "products"("id") on delete cascade,
  foreign key("related_product_id") references "products"("id") on delete cascade
);
CREATE UNIQUE INDEX "product_relationship_unique" on "product_relationships"(
  "product_id",
  "related_product_id",
  "relationship_type"
);
CREATE INDEX "product_relationships_relationship_type_priority_index" on "product_relationships"(
  "relationship_type",
  "priority"
);
CREATE TABLE IF NOT EXISTS "groups"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "groups_team_id_name_unique" on "groups"(
  "team_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "group_people"(
  "id" integer primary key autoincrement not null,
  "group_id" integer not null,
  "people_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("group_id") references "groups"("id") on delete cascade,
  foreign key("people_id") references "people"("id") on delete cascade
);
CREATE UNIQUE INDEX "group_people_group_id_people_id_unique" on "group_people"(
  "group_id",
  "people_id"
);
CREATE TABLE IF NOT EXISTS "account_people"(
  "id" integer primary key autoincrement not null,
  "account_id" integer not null,
  "people_id" integer not null,
  "is_primary" tinyint(1) not null default '0',
  "role" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("account_id") references "accounts"("id") on delete cascade,
  foreign key("people_id") references "people"("id") on delete cascade
);
CREATE UNIQUE INDEX "account_people_account_id_people_id_unique" on "account_people"(
  "account_id",
  "people_id"
);
CREATE INDEX "account_people_is_primary_index" on "account_people"(
  "is_primary"
);
CREATE TABLE IF NOT EXISTS "permissions"(
  "id" integer primary key autoincrement not null,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE UNIQUE INDEX "permissions_name_guard_name_unique" on "permissions"(
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "roles"(
  "id" integer primary key autoincrement not null,
  "team_id" integer,
  "name" varchar not null,
  "guard_name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "roles_team_foreign_key_index" on "roles"("team_id");
CREATE UNIQUE INDEX "roles_team_id_name_guard_name_unique" on "roles"(
  "team_id",
  "name",
  "guard_name"
);
CREATE TABLE IF NOT EXISTS "model_has_permissions"(
  "permission_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  "team_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  primary key("team_id", "permission_id", "model_id", "model_type")
);
CREATE INDEX "model_has_permissions_model_id_model_type_index" on "model_has_permissions"(
  "model_id",
  "model_type"
);
CREATE INDEX "model_has_permissions_team_foreign_key_index" on "model_has_permissions"(
  "team_id"
);
CREATE TABLE IF NOT EXISTS "model_has_roles"(
  "role_id" integer not null,
  "model_type" varchar not null,
  "model_id" integer not null,
  "team_id" integer not null,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("team_id", "role_id", "model_id", "model_type")
);
CREATE INDEX "model_has_roles_model_id_model_type_index" on "model_has_roles"(
  "model_id",
  "model_type"
);
CREATE INDEX "model_has_roles_team_foreign_key_index" on "model_has_roles"(
  "team_id"
);
CREATE TABLE IF NOT EXISTS "role_has_permissions"(
  "permission_id" integer not null,
  "role_id" integer not null,
  foreign key("permission_id") references "permissions"("id") on delete cascade,
  foreign key("role_id") references "roles"("id") on delete cascade,
  primary key("permission_id", "role_id")
);
CREATE TABLE IF NOT EXISTS "people_emails"(
  "id" integer primary key autoincrement not null,
  "people_id" integer not null,
  "email" varchar not null,
  "type" varchar not null default 'work',
  "is_primary" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("people_id") references "people"("id") on delete cascade
);
CREATE UNIQUE INDEX "people_emails_people_id_email_unique" on "people_emails"(
  "people_id",
  "email"
);
CREATE INDEX "people_emails_is_primary_index" on "people_emails"("is_primary");
CREATE TABLE IF NOT EXISTS "project_user"(
  "id" integer primary key autoincrement not null,
  "project_id" integer not null,
  "user_id" integer not null,
  "role" varchar,
  "allocation_percentage" numeric not null default '100',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("project_id") references "projects"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete cascade
);
CREATE UNIQUE INDEX "project_user_project_id_user_id_unique" on "project_user"(
  "project_id",
  "user_id"
);
CREATE TABLE IF NOT EXISTS "project_task"(
  "id" integer primary key autoincrement not null,
  "project_id" integer not null,
  "task_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("project_id") references "projects"("id") on delete cascade,
  foreign key("task_id") references "tasks"("id") on delete cascade
);
CREATE UNIQUE INDEX "project_task_project_id_task_id_unique" on "project_task"(
  "project_id",
  "task_id"
);
CREATE INDEX "task_deps_task_id_index" on "task_dependencies"("task_id");
CREATE INDEX "task_deps_depends_on_index" on "task_dependencies"(
  "depends_on_task_id"
);
CREATE INDEX "project_task_task_id_index" on "project_task"("task_id");
CREATE INDEX "project_task_project_id_index" on "project_task"("project_id");
CREATE INDEX "task_time_entries_billable_index" on "task_time_entries"(
  "is_billable"
);
CREATE INDEX "task_time_entries_task_billable_index" on "task_time_entries"(
  "task_id",
  "is_billable"
);
CREATE TABLE IF NOT EXISTS "task_template_dependencies"(
  "id" integer primary key autoincrement not null,
  "task_template_id" integer not null,
  "depends_on_template_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("task_template_id") references "task_templates"("id") on delete cascade,
  foreign key("depends_on_template_id") references "task_templates"("id") on delete cascade
);
CREATE UNIQUE INDEX "task_template_deps_unique" on "task_template_dependencies"(
  "task_template_id",
  "depends_on_template_id"
);
CREATE TABLE IF NOT EXISTS "employees"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "user_id" integer,
  "manager_id" integer,
  "first_name" varchar not null,
  "last_name" varchar not null,
  "email" varchar,
  "phone" varchar,
  "mobile" varchar,
  "employee_number" varchar,
  "department" varchar,
  "role" varchar,
  "title" varchar,
  "status" varchar not null default 'active',
  "start_date" date,
  "end_date" date,
  "address" text,
  "city" varchar,
  "state" varchar,
  "postal_code" varchar,
  "country" varchar,
  "emergency_contact_name" varchar,
  "emergency_contact_phone" varchar,
  "emergency_contact_relationship" varchar,
  "skills" text,
  "certifications" text,
  "performance_notes" text,
  "performance_rating" numeric,
  "vacation_days_total" numeric not null default '0',
  "vacation_days_used" numeric not null default '0',
  "sick_days_total" numeric not null default '0',
  "sick_days_used" numeric not null default '0',
  "has_portal_access" tinyint(1) not null default '0',
  "payroll_id" varchar,
  "payroll_metadata" text,
  "capacity_hours_per_week" numeric not null default '40',
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade,
  foreign key("user_id") references "users"("id") on delete set null,
  foreign key("manager_id") references "employees"("id") on delete set null
);
CREATE INDEX "employees_team_id_status_index" on "employees"(
  "team_id",
  "status"
);
CREATE INDEX "employees_team_id_department_index" on "employees"(
  "team_id",
  "department"
);
CREATE INDEX "employees_employee_number_index" on "employees"(
  "employee_number"
);
CREATE UNIQUE INDEX "employees_employee_number_unique" on "employees"(
  "employee_number"
);
CREATE TABLE IF NOT EXISTS "employee_documents"(
  "id" integer primary key autoincrement not null,
  "employee_id" integer not null,
  "name" varchar not null,
  "type" varchar,
  "description" text,
  "file_path" varchar not null,
  "expiry_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("employee_id") references "employees"("id") on delete cascade
);
CREATE INDEX "employee_documents_employee_id_type_index" on "employee_documents"(
  "employee_id",
  "type"
);
CREATE TABLE IF NOT EXISTS "employee_time_off"(
  "id" integer primary key autoincrement not null,
  "employee_id" integer not null,
  "approved_by" integer,
  "type" varchar not null,
  "start_date" date not null,
  "end_date" date not null,
  "days" numeric not null,
  "status" varchar not null default 'pending',
  "reason" text,
  "rejection_reason" text,
  "approved_at" datetime,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("employee_id") references "employees"("id") on delete cascade,
  foreign key("approved_by") references "users"("id") on delete set null
);
CREATE INDEX "employee_time_off_employee_id_status_index" on "employee_time_off"(
  "employee_id",
  "status"
);
CREATE INDEX "employee_time_off_start_date_end_date_index" on "employee_time_off"(
  "start_date",
  "end_date"
);
CREATE TABLE IF NOT EXISTS "employee_allocations"(
  "id" integer primary key autoincrement not null,
  "employee_id" integer not null,
  "allocatable_type" varchar not null,
  "allocatable_id" integer not null,
  "allocation_percentage" numeric not null default '0',
  "start_date" date,
  "end_date" date,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("employee_id") references "employees"("id") on delete cascade
);
CREATE INDEX "employee_allocations_allocatable_type_allocatable_id_index" on "employee_allocations"(
  "allocatable_type",
  "allocatable_id"
);
CREATE INDEX "employee_allocations_employee_id_start_date_end_date_index" on "employee_allocations"(
  "employee_id",
  "start_date",
  "end_date"
);
CREATE TABLE IF NOT EXISTS "contact_personas"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE TABLE IF NOT EXISTS "contact_roles"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "name" varchar not null,
  "description" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("team_id") references "teams"("id") on delete cascade
);
CREATE UNIQUE INDEX "contact_roles_team_id_name_unique" on "contact_roles"(
  "team_id",
  "name"
);
CREATE TABLE IF NOT EXISTS "contact_role_people"(
  "id" integer primary key autoincrement not null,
  "people_id" integer not null,
  "contact_role_id" integer not null,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("people_id") references "people"("id") on delete cascade,
  foreign key("contact_role_id") references "contact_roles"("id") on delete cascade
);
CREATE UNIQUE INDEX "contact_role_people_people_id_contact_role_id_unique" on "contact_role_people"(
  "people_id",
  "contact_role_id"
);
CREATE TABLE IF NOT EXISTS "communication_preferences"(
  "id" integer primary key autoincrement not null,
  "people_id" integer not null,
  "email_opt_in" tinyint(1) not null default '1',
  "phone_opt_in" tinyint(1) not null default '1',
  "sms_opt_in" tinyint(1) not null default '1',
  "postal_opt_in" tinyint(1) not null default '1',
  "preferred_channel" varchar,
  "preferred_time" varchar,
  "do_not_contact" tinyint(1) not null default '0',
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("people_id") references "people"("id") on delete cascade
);
CREATE UNIQUE INDEX "communication_preferences_people_id_unique" on "communication_preferences"(
  "people_id"
);
CREATE TABLE IF NOT EXISTS "portal_users"(
  "id" integer primary key autoincrement not null,
  "people_id" integer not null,
  "email" varchar not null,
  "password" varchar not null,
  "is_active" tinyint(1) not null default '1',
  "last_login_at" datetime,
  "remember_token" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("people_id") references "people"("id") on delete cascade
);
CREATE UNIQUE INDEX "portal_users_people_id_unique" on "portal_users"(
  "people_id"
);
CREATE UNIQUE INDEX "portal_users_email_unique" on "portal_users"("email");
CREATE TABLE IF NOT EXISTS "contact_merge_logs"(
  "id" integer primary key autoincrement not null,
  "primary_contact_id" integer not null,
  "duplicate_contact_id" integer not null,
  "merged_by" integer not null,
  "merge_data" text,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("primary_contact_id") references "people"("id") on delete cascade,
  foreign key("duplicate_contact_id") references "people"("id") on delete cascade,
  foreign key("merged_by") references "users"("id")
);
CREATE TABLE IF NOT EXISTS "model_reference_counters"(
  "id" integer primary key autoincrement not null,
  "key" varchar not null,
  "value" integer not null default '1',
  "created_at" datetime,
  "updated_at" datetime
);
CREATE INDEX "model_reference_counters_key_index" on "model_reference_counters"(
  "key"
);
CREATE UNIQUE INDEX "model_reference_counters_key_unique" on "model_reference_counters"(
  "key"
);
CREATE TABLE IF NOT EXISTS "companies"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "account_owner_id" integer,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "creation_source" varchar not null,
  "website" varchar,
  "industry" varchar,
  "revenue" numeric,
  "employee_count" integer,
  "description" text,
  "parent_company_id" integer,
  "account_type" varchar,
  "ownership" varchar,
  "phone" varchar,
  "primary_email" varchar,
  "currency_code" varchar not null default('USD'),
  "social_links" text,
  "billing_street" varchar,
  "billing_city" varchar,
  "billing_state" varchar,
  "billing_postal_code" varchar,
  "billing_country" varchar,
  "shipping_street" varchar,
  "shipping_city" varchar,
  "shipping_state" varchar,
  "shipping_postal_code" varchar,
  "shipping_country" varchar,
  "billing_country_id" integer,
  "billing_state_id" integer,
  "billing_city_id" integer,
  "shipping_country_id" integer,
  "shipping_state_id" integer,
  "shipping_city_id" integer,
  "addresses" text,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("shipping_city_id") references cities("id") on delete set null on update no action,
  foreign key("shipping_state_id") references states("id") on delete set null on update no action,
  foreign key("shipping_country_id") references countries("id") on delete set null on update no action,
  foreign key("billing_city_id") references cities("id") on delete set null on update no action,
  foreign key("billing_state_id") references states("id") on delete set null on update no action,
  foreign key("billing_country_id") references countries("id") on delete set null on update no action,
  foreign key("account_owner_id") references users("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("parent_company_id") references companies("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "companies_name_index" on "companies"("name");
CREATE INDEX "companies_parent_company_id_index" on "companies"(
  "parent_company_id"
);
CREATE INDEX "companies_website_index" on "companies"("website");
CREATE INDEX "idx_companies_team_name" on "companies"("team_id", "name");
CREATE TABLE IF NOT EXISTS "company_revenues"(
  "id" integer primary key autoincrement not null,
  "company_id" integer not null,
  "team_id" integer not null,
  "creator_id" integer,
  "year" integer not null,
  "amount" numeric not null,
  "currency_code" varchar not null default('USD'),
  "creation_source" varchar not null default('web'),
  "created_at" datetime,
  "updated_at" datetime,
  "editor_id" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("company_id") references companies("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "company_revenues_company_id_year_unique" on "company_revenues"(
  "company_id",
  "year"
);
CREATE INDEX "company_revenues_team_id_year_index" on "company_revenues"(
  "team_id",
  "year"
);
CREATE TABLE IF NOT EXISTS "document_templates"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "description" varchar,
  "body" text,
  "is_default" tinyint(1) not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "editor_id" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "documents"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "template_id" integer,
  "current_version_id" integer,
  "title" varchar not null,
  "description" text,
  "visibility" varchar not null default('private'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("current_version_id") references document_versions("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("template_id") references document_templates("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "invoices"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "contact_id" integer,
  "opportunity_id" integer,
  "parent_invoice_id" integer,
  "sequence" integer not null,
  "number" varchar not null,
  "status" varchar not null,
  "issue_date" date,
  "due_date" date,
  "payment_terms" varchar,
  "currency_code" varchar not null default('USD'),
  "fx_rate" numeric not null default('1'),
  "subtotal" numeric not null default('0'),
  "discount_total" numeric not null default('0'),
  "tax_total" numeric not null default('0'),
  "late_fee_rate" numeric not null default('0'),
  "late_fee_amount" numeric not null default('0'),
  "late_fee_applied_at" datetime,
  "total" numeric not null default('0'),
  "balance_due" numeric not null default('0'),
  "template_key" varchar,
  "reminder_policy" varchar,
  "sent_at" datetime,
  "paid_at" datetime,
  "last_reminded_at" datetime,
  "is_recurring_template" tinyint(1) not null default('0'),
  "recurring_frequency" varchar,
  "recurring_interval" integer not null default('1'),
  "recurring_starts_at" date,
  "recurring_ends_at" date,
  "next_issue_at" date,
  "notes" text,
  "terms" text,
  "creation_source" varchar not null default('web'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "order_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("order_id") references orders("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("contact_id") references people("id") on delete set null on update no action,
  foreign key("opportunity_id") references opportunities("id") on delete set null on update no action,
  foreign key("parent_invoice_id") references invoices("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "invoices_number_unique" on "invoices"("number");
CREATE UNIQUE INDEX "invoices_team_id_sequence_unique" on "invoices"(
  "team_id",
  "sequence"
);
CREATE TABLE IF NOT EXISTS "knowledge_articles"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "category_id" integer,
  "creator_id" integer,
  "author_id" integer,
  "approver_id" integer,
  "title" varchar not null,
  "slug" varchar not null,
  "status" varchar not null default('draft'),
  "visibility" varchar not null default('internal'),
  "summary" varchar,
  "content" text,
  "meta_title" varchar,
  "meta_description" text,
  "meta_keywords" text,
  "allow_comments" tinyint(1) not null default('1'),
  "allow_ratings" tinyint(1) not null default('1'),
  "is_featured" tinyint(1) not null default('0'),
  "published_at" datetime,
  "archived_at" datetime,
  "review_due_at" datetime,
  "view_count" integer not null default('0'),
  "helpful_count" integer not null default('0'),
  "not_helpful_count" integer not null default('0'),
  "approval_notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "current_version_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("current_version_id") references knowledge_article_versions("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("category_id") references knowledge_categories("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("author_id") references users("id") on delete set null on update no action,
  foreign key("approver_id") references users("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "knowledge_articles_status_visibility_index" on "knowledge_articles"(
  "status",
  "visibility"
);
CREATE UNIQUE INDEX "knowledge_articles_team_id_slug_unique" on "knowledge_articles"(
  "team_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "knowledge_categories"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "parent_id" integer,
  "creator_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "visibility" varchar not null default('internal'),
  "description" text,
  "position" integer not null default('0'),
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("parent_id") references knowledge_categories("id") on delete cascade on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "knowledge_categories_team_id_position_index" on "knowledge_categories"(
  "team_id",
  "position"
);
CREATE UNIQUE INDEX "knowledge_categories_team_id_slug_unique" on "knowledge_categories"(
  "team_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "knowledge_faqs"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "article_id" integer,
  "creator_id" integer,
  "question" varchar not null,
  "answer" text not null,
  "status" varchar not null default('draft'),
  "visibility" varchar not null default('public'),
  "position" integer not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("article_id") references knowledge_articles("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "knowledge_faqs_team_id_status_index" on "knowledge_faqs"(
  "team_id",
  "status"
);
CREATE TABLE IF NOT EXISTS "knowledge_tags"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "description" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "knowledge_tags_team_id_slug_unique" on "knowledge_tags"(
  "team_id",
  "slug"
);
CREATE TABLE IF NOT EXISTS "knowledge_template_responses"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "category_id" integer,
  "creator_id" integer,
  "title" varchar not null,
  "body" text not null,
  "visibility" varchar not null default('internal'),
  "is_active" tinyint(1) not null default('1'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("category_id") references knowledge_categories("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "knowledge_template_responses_team_id_visibility_index" on "knowledge_template_responses"(
  "team_id",
  "visibility"
);
CREATE TABLE IF NOT EXISTS "leads"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "assigned_to_id" integer,
  "company_id" integer,
  "qualified_by_id" integer,
  "converted_by_id" integer,
  "converted_company_id" integer,
  "converted_contact_id" integer,
  "converted_opportunity_id" integer,
  "duplicate_of_id" integer,
  "import_id" integer,
  "name" varchar not null,
  "job_title" varchar,
  "company_name" varchar,
  "email" varchar,
  "phone" varchar,
  "mobile" varchar,
  "website" varchar,
  "source" varchar not null default('website'),
  "status" varchar not null default('new'),
  "score" integer not null default('0'),
  "grade" varchar,
  "assignment_strategy" varchar not null default('manual'),
  "territory" varchar,
  "nurture_status" varchar not null default('not_started'),
  "nurture_program" varchar,
  "next_nurture_touch_at" datetime,
  "qualified_at" datetime,
  "qualification_notes" text,
  "converted_at" datetime,
  "last_activity_at" datetime,
  "duplicate_score" numeric,
  "web_form_key" varchar,
  "web_form_payload" text,
  "creation_source" varchar not null default('web'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "order_column" varchar,
  "description" text,
  "lead_value" numeric,
  "lead_type" varchar,
  "expected_close_date" date,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("import_id") references imports("id") on delete set null on update no action,
  foreign key("duplicate_of_id") references leads("id") on delete set null on update no action,
  foreign key("converted_opportunity_id") references opportunities("id") on delete set null on update no action,
  foreign key("converted_contact_id") references people("id") on delete set null on update no action,
  foreign key("converted_company_id") references companies("id") on delete set null on update no action,
  foreign key("converted_by_id") references users("id") on delete set null on update no action,
  foreign key("qualified_by_id") references users("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("assigned_to_id") references users("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "leads_company_name_index" on "leads"("company_name");
CREATE INDEX "leads_created_at_index" on "leads"("created_at");
CREATE INDEX "leads_email_index" on "leads"("email");
CREATE INDEX "leads_phone_index" on "leads"("phone");
CREATE INDEX "leads_source_index" on "leads"("source");
CREATE INDEX "leads_team_id_assigned_to_id_index" on "leads"(
  "team_id",
  "assigned_to_id"
);
CREATE INDEX "leads_team_id_status_index" on "leads"("team_id", "status");
CREATE INDEX "leads_territory_index" on "leads"("territory");
CREATE TABLE IF NOT EXISTS "notes"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "creation_source" varchar not null,
  "category" varchar,
  "visibility" varchar not null default('internal'),
  "is_template" tinyint(1) not null default('0'),
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "idx_notes_creator" on "notes"("creator_id");
CREATE INDEX "idx_notes_team_created" on "notes"("team_id", "created_at");
CREATE INDEX "notes_category_index" on "notes"("category");
CREATE INDEX "notes_visibility_index" on "notes"("visibility");
CREATE TABLE IF NOT EXISTS "opportunities"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "contact_id" integer,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "creation_source" varchar not null,
  "order_column" varchar,
  "owner_id" integer,
  "closed_at" datetime,
  "closed_by_id" integer,
  "account_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  "stage" varchar,
  "probability" numeric,
  "amount" numeric,
  "weighted_amount" numeric,
  "expected_close_date" date,
  "competitors" text,
  "next_steps" text,
  "win_loss_reason" varchar,
  "forecast_category" varchar,
  foreign key("account_id") references accounts("id") on delete set null on update no action,
  foreign key("contact_id") references people("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("owner_id") references users("id") on delete set null on update no action,
  foreign key("closed_by_id") references users("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "idx_opportunities_creator" on "opportunities"("creator_id");
CREATE INDEX "idx_opportunities_team_created" on "opportunities"(
  "team_id",
  "created_at"
);
CREATE INDEX "opportunities_account_id_index" on "opportunities"("account_id");
CREATE TABLE IF NOT EXISTS "orders"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "contact_id" integer,
  "opportunity_id" integer,
  "quote_id" integer,
  "status" varchar not null default('draft'),
  "currency_code" varchar not null default('USD'),
  "subtotal" numeric not null default('0'),
  "tax_total" numeric not null default('0'),
  "total" numeric not null default('0'),
  "expected_delivery_date" date,
  "line_items" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "sequence" integer,
  "number" varchar,
  "fulfillment_status" varchar not null default('pending'),
  "ordered_at" date,
  "fulfillment_due_at" date,
  "fulfilled_at" datetime,
  "payment_terms" varchar,
  "fx_rate" numeric not null default('1'),
  "discount_total" numeric not null default('0'),
  "balance_due" numeric not null default('0'),
  "paid_total" numeric not null default('0'),
  "invoiced_total" numeric not null default('0'),
  "invoice_template_key" varchar,
  "quote_reference" varchar,
  "notes" text,
  "terms" text,
  "creation_source" varchar not null default('web'),
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("quote_id") references quotes("id") on delete set null on update no action,
  foreign key("opportunity_id") references opportunities("id") on delete set null on update no action,
  foreign key("contact_id") references people("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "orders_number_unique" on "orders"("number");
CREATE UNIQUE INDEX "orders_team_id_sequence_unique" on "orders"(
  "team_id",
  "sequence"
);
CREATE TABLE IF NOT EXISTS "pdf_templates"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "key" varchar not null,
  "entity_type" varchar,
  "description" text,
  "layout" text not null,
  "merge_fields" text,
  "styling" text,
  "watermark" text,
  "permissions" text,
  "encryption_enabled" tinyint(1) not null default('0'),
  "encryption_password" varchar,
  "version" integer not null default('1'),
  "parent_template_id" integer,
  "is_active" tinyint(1) not null default('1'),
  "is_archived" tinyint(1) not null default('0'),
  "archived_at" datetime,
  "metadata" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("parent_template_id") references pdf_templates("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "pdf_templates_key_is_active_index" on "pdf_templates"(
  "key",
  "is_active"
);
CREATE UNIQUE INDEX "pdf_templates_key_unique" on "pdf_templates"("key");
CREATE INDEX "pdf_templates_team_id_entity_type_index" on "pdf_templates"(
  "team_id",
  "entity_type"
);
CREATE TABLE IF NOT EXISTS "people"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "name" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "creation_source" varchar not null,
  "primary_email" varchar,
  "alternate_email" varchar,
  "phone_mobile" varchar,
  "phone_office" varchar,
  "phone_home" varchar,
  "phone_fax" varchar,
  "job_title" varchar,
  "department" varchar,
  "reports_to_id" integer,
  "birthdate" date,
  "assistant_name" varchar,
  "assistant_phone" varchar,
  "assistant_email" varchar,
  "address_street" varchar,
  "address_city" varchar,
  "address_state" varchar,
  "address_postal_code" varchar,
  "address_country" varchar,
  "social_links" text,
  "lead_source" varchar,
  "is_portal_user" tinyint(1) not null default('0'),
  "portal_username" varchar,
  "portal_last_login_at" datetime,
  "sync_enabled" tinyint(1) not null default('0'),
  "sync_reference" varchar,
  "synced_at" datetime,
  "segments" text,
  "address_country_id" integer,
  "address_state_id" integer,
  "address_city_id" integer,
  "role" varchar,
  "persona_id" integer,
  "primary_company_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("primary_company_id") references companies("id") on delete set null on update no action,
  foreign key("persona_id") references contact_personas("id") on delete set null on update no action,
  foreign key("reports_to_id") references people("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("address_country_id") references countries("id") on delete set null on update no action,
  foreign key("address_state_id") references states("id") on delete set null on update no action,
  foreign key("address_city_id") references cities("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "idx_people_team_name" on "people"("team_id", "name");
CREATE TABLE IF NOT EXISTS "projects"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "template_id" integer,
  "name" varchar not null,
  "slug" varchar not null,
  "description" text,
  "status" varchar not null default('planning'),
  "start_date" date,
  "end_date" date,
  "actual_start_date" date,
  "actual_end_date" date,
  "budget" numeric,
  "actual_cost" numeric not null default('0'),
  "currency" varchar not null default('USD'),
  "percent_complete" numeric not null default('0'),
  "phases" text,
  "milestones" text,
  "deliverables" text,
  "risks" text,
  "issues" text,
  "documentation" text,
  "dashboard_config" text,
  "is_template" tinyint(1) not null default('0'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("template_id") references projects("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "projects_end_date_index" on "projects"("end_date");
CREATE INDEX "projects_percent_complete_index" on "projects"(
  "percent_complete"
);
CREATE UNIQUE INDEX "projects_slug_unique" on "projects"("slug");
CREATE INDEX "projects_start_date_index" on "projects"("start_date");
CREATE INDEX "projects_team_end_date_index" on "projects"(
  "team_id",
  "end_date"
);
CREATE INDEX "projects_team_id_is_template_index" on "projects"(
  "team_id",
  "is_template"
);
CREATE INDEX "projects_team_id_status_index" on "projects"(
  "team_id",
  "status"
);
CREATE INDEX "projects_team_start_date_index" on "projects"(
  "team_id",
  "start_date"
);
CREATE TABLE IF NOT EXISTS "purchase_orders"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "vendor_id" integer,
  "company_id" integer,
  "order_id" integer,
  "sequence" integer not null,
  "number" varchar not null,
  "status" varchar not null default('draft'),
  "ordered_at" date,
  "expected_delivery_date" date,
  "approved_at" datetime,
  "issued_at" datetime,
  "last_received_at" datetime,
  "closed_at" datetime,
  "cancelled_at" datetime,
  "payment_terms" varchar,
  "shipping_terms" varchar,
  "ship_method" varchar,
  "ship_to_address" text,
  "bill_to_address" text,
  "currency_code" varchar not null default('USD'),
  "fx_rate" numeric not null default('1'),
  "subtotal" numeric not null default('0'),
  "tax_total" numeric not null default('0'),
  "freight_total" numeric not null default('0'),
  "fee_total" numeric not null default('0'),
  "total" numeric not null default('0'),
  "received_cost" numeric not null default('0'),
  "outstanding_commitment" numeric not null default('0'),
  "notes" text,
  "terms" text,
  "creation_source" varchar not null default('web'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("order_id") references orders("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("vendor_id") references vendors("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE UNIQUE INDEX "purchase_orders_number_unique" on "purchase_orders"(
  "number"
);
CREATE UNIQUE INDEX "purchase_orders_team_id_sequence_unique" on "purchase_orders"(
  "team_id",
  "sequence"
);
CREATE TABLE IF NOT EXISTS "task_templates"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "name" varchar not null,
  "description" text,
  "estimated_duration_minutes" integer,
  "is_milestone" tinyint(1) not null default('0'),
  "default_assignees" text,
  "checklist_items" text,
  "created_at" datetime,
  "updated_at" datetime,
  "editor_id" integer,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("editor_id") references "users"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "tasks"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "title" varchar not null,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "creation_source" varchar not null,
  "order_column" varchar,
  "parent_id" integer,
  "start_date" datetime,
  "end_date" datetime,
  "estimated_duration_minutes" integer,
  "percent_complete" numeric not null default('0'),
  "is_milestone" tinyint(1) not null default('0'),
  "template_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  foreign key("template_id") references task_templates("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("parent_id") references tasks("id") on delete set null on update no action,
  foreign key("editor_id") references "users"("id") on delete set null,
  foreign key("deleted_by") references "users"("id") on delete set null
);
CREATE INDEX "idx_tasks_creator" on "tasks"("creator_id");
CREATE INDEX "idx_tasks_team_created" on "tasks"("team_id", "created_at");
CREATE INDEX "tasks_end_date_index" on "tasks"("end_date");
CREATE INDEX "tasks_is_milestone_index" on "tasks"("is_milestone");
CREATE INDEX "tasks_percent_complete_index" on "tasks"("percent_complete");
CREATE INDEX "tasks_start_date_index" on "tasks"("start_date");
CREATE INDEX "tasks_team_milestone_index" on "tasks"(
  "team_id",
  "is_milestone"
);
CREATE TABLE IF NOT EXISTS "calendar_events"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "title" varchar not null,
  "type" varchar not null default('meeting'),
  "status" varchar not null default('scheduled'),
  "is_all_day" tinyint(1) not null default('0'),
  "start_at" datetime not null,
  "end_at" datetime,
  "location" varchar,
  "meeting_url" varchar,
  "reminder_minutes_before" integer,
  "attendees" text,
  "related_type" varchar,
  "related_id" integer,
  "sync_provider" varchar,
  "sync_status" varchar not null default('not_synced'),
  "sync_external_id" varchar,
  "creation_source" varchar not null default('web'),
  "notes" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "recurrence_rule" varchar,
  "recurrence_end_date" datetime,
  "recurrence_parent_id" integer,
  "agenda" text,
  "minutes" text,
  "room_booking" varchar,
  "editor_id" integer,
  "deleted_by" integer,
  "zap_schedule_id" integer,
  "zap_metadata" text,
  foreign key("deleted_by") references users("id") on delete set null on update no action,
  foreign key("editor_id") references users("id") on delete set null on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("recurrence_parent_id") references calendar_events("id") on delete set null on update no action,
  foreign key("zap_schedule_id") references "schedules"("id") on delete set null
);
CREATE INDEX "calendar_events_creator_id_index" on "calendar_events"(
  "creator_id"
);
CREATE INDEX "calendar_events_recurrence_end_date_index" on "calendar_events"(
  "recurrence_end_date"
);
CREATE INDEX "calendar_events_recurrence_parent_id_index" on "calendar_events"(
  "recurrence_parent_id"
);
CREATE INDEX "calendar_events_recurrence_parent_index" on "calendar_events"(
  "recurrence_parent_id"
);
CREATE INDEX "calendar_events_recurrence_rule_index" on "calendar_events"(
  "recurrence_rule"
);
CREATE INDEX "calendar_events_related_type_related_id_index" on "calendar_events"(
  "related_type",
  "related_id"
);
CREATE INDEX "calendar_events_sync_index" on "calendar_events"(
  "sync_provider",
  "sync_external_id"
);
CREATE INDEX "calendar_events_team_id_start_at_index" on "calendar_events"(
  "team_id",
  "start_at"
);
CREATE INDEX "calendar_events_team_id_status_index" on "calendar_events"(
  "team_id",
  "status"
);
CREATE INDEX "calendar_events_team_type_status_start_index" on "calendar_events"(
  "team_id",
  "type",
  "status",
  "start_at"
);
CREATE INDEX "calendar_events_type_index" on "calendar_events"("type");
CREATE INDEX "idx_parent_start" on "calendar_events"(
  "recurrence_parent_id",
  "start_at"
);
CREATE INDEX "idx_sync_status_provider" on "calendar_events"(
  "sync_status",
  "sync_provider"
);
CREATE INDEX "idx_team_date_range" on "calendar_events"(
  "team_id",
  "start_at",
  "end_at"
);
CREATE INDEX "idx_team_recurrence_parent" on "calendar_events"(
  "team_id",
  "recurrence_parent_id"
);
CREATE INDEX "idx_team_recurrence_rule" on "calendar_events"(
  "team_id",
  "recurrence_rule"
);
CREATE TABLE IF NOT EXISTS "quotes"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "contact_id" integer,
  "opportunity_id" integer,
  "title" varchar not null,
  "status" varchar not null default('draft'),
  "currency_code" varchar not null default('USD'),
  "subtotal" numeric not null default('0'),
  "tax_total" numeric not null default('0'),
  "total" numeric not null default('0'),
  "valid_until" date,
  "accepted_at" datetime,
  "rejected_at" datetime,
  "decision_note" text,
  "line_items" text,
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "editor_id" integer,
  "deleted_by" integer,
  "owner_id" integer,
  "lead_id" integer,
  "description" text,
  "discount_total" numeric not null default '0',
  "billing_address" text,
  "shipping_address" text,
  foreign key("deleted_by") references users("id") on delete set null on update no action,
  foreign key("editor_id") references users("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("contact_id") references people("id") on delete set null on update no action,
  foreign key("opportunity_id") references opportunities("id") on delete set null on update no action,
  foreign key("owner_id") references "users"("id") on delete set null,
  foreign key("lead_id") references "leads"("id") on delete set null
);
CREATE TABLE IF NOT EXISTS "file_system_items"(
  "id" integer primary key autoincrement not null,
  "parent_id" integer,
  "name" varchar not null,
  "type" varchar not null,
  "file_type" varchar,
  "size" integer,
  "duration" integer,
  "thumbnail" varchar,
  "storage_path" varchar,
  "created_at" datetime,
  "updated_at" datetime,
  foreign key("parent_id") references "file_system_items"("id") on delete cascade
);
CREATE INDEX "file_system_items_type_index" on "file_system_items"("type");
CREATE INDEX "file_system_items_file_type_index" on "file_system_items"(
  "file_type"
);
CREATE UNIQUE INDEX "file_system_items_parent_id_name_unique" on "file_system_items"(
  "parent_id",
  "name"
);
CREATE INDEX "opportunities_stage_index" on "opportunities"("stage");
CREATE INDEX "opportunities_expected_close_date_index" on "opportunities"(
  "expected_close_date"
);
CREATE INDEX "opportunities_forecast_category_index" on "opportunities"(
  "forecast_category"
);
CREATE TABLE IF NOT EXISTS "cases"(
  "id" integer primary key autoincrement not null,
  "team_id" integer not null,
  "creator_id" integer,
  "company_id" integer,
  "contact_id" integer,
  "assigned_to_id" integer,
  "assigned_team_id" integer,
  "case_number" varchar not null,
  "subject" varchar not null,
  "description" text,
  "status" varchar not null default('new'),
  "priority" varchar not null default('p3'),
  "type" varchar not null default('question'),
  "channel" varchar not null default('internal'),
  "queue" varchar,
  "sla_due_at" datetime,
  "first_response_at" datetime,
  "resolved_at" datetime,
  "escalated_at" datetime,
  "resolution_summary" text,
  "thread_reference" varchar,
  "customer_portal_url" varchar,
  "knowledge_base_reference" varchar,
  "email_message_id" varchar,
  "creation_source" varchar not null default('web'),
  "created_at" datetime,
  "updated_at" datetime,
  "deleted_at" datetime,
  "escalation_level" integer not null default('0'),
  "sla_breach_at" datetime,
  "sla_breached" tinyint(1) not null default('0'),
  "response_time_minutes" integer,
  "resolution_time_minutes" integer,
  "portal_visible" tinyint(1) not null default('0'),
  "knowledge_article_id" integer,
  "account_id" integer,
  "editor_id" integer,
  "deleted_by" integer,
  "thread_id" varchar,
  "parent_case_id" integer,
  foreign key("deleted_by") references users("id") on delete set null on update no action,
  foreign key("editor_id") references users("id") on delete set null on update no action,
  foreign key("knowledge_article_id") references knowledge_articles("id") on delete set null on update no action,
  foreign key("team_id") references teams("id") on delete cascade on update no action,
  foreign key("creator_id") references users("id") on delete set null on update no action,
  foreign key("company_id") references companies("id") on delete set null on update no action,
  foreign key("contact_id") references people("id") on delete set null on update no action,
  foreign key("assigned_to_id") references users("id") on delete set null on update no action,
  foreign key("assigned_team_id") references teams("id") on delete set null on update no action,
  foreign key("account_id") references accounts("id") on delete set null on update no action,
  foreign key("parent_case_id") references "cases"("id") on delete set null
);
CREATE INDEX "cases_account_id_index" on "cases"("account_id");
CREATE UNIQUE INDEX "cases_case_number_unique" on "cases"("case_number");
CREATE INDEX "cases_channel_index" on "cases"("channel");
CREATE INDEX "cases_escalation_level_index" on "cases"("escalation_level");
CREATE INDEX "cases_queue_priority_index" on "cases"("queue", "priority");
CREATE INDEX "cases_sla_due_at_sla_breached_index" on "cases"(
  "sla_due_at",
  "sla_breached"
);
CREATE INDEX "cases_team_id_status_index" on "cases"("team_id", "status");
CREATE INDEX "idx_cases_assigned_to" on "cases"("assigned_to_id");
CREATE INDEX "idx_cases_company" on "cases"("company_id");
CREATE INDEX "idx_cases_priority_status" on "cases"("priority", "status");
CREATE INDEX "idx_cases_sla_overdue" on "cases"("sla_due_at", "resolved_at");
CREATE INDEX "idx_cases_team_created" on "cases"("team_id", "created_at");
CREATE INDEX "idx_cases_type" on "cases"("type");
CREATE INDEX "cases_thread_id_index" on "cases"("thread_id");
CREATE INDEX "cases_parent_case_id_index" on "cases"("parent_case_id");

INSERT INTO migrations VALUES(1,'0001_01_01_000000_create_users_table',1);
INSERT INTO migrations VALUES(2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO migrations VALUES(3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO migrations VALUES(4,'2020_07_07_055656_create_countries_table',1);
INSERT INTO migrations VALUES(5,'2020_07_07_055725_create_cities_table',1);
INSERT INTO migrations VALUES(6,'2020_07_07_055746_create_timezones_table',1);
INSERT INTO migrations VALUES(7,'2021_10_19_071730_create_states_table',1);
INSERT INTO migrations VALUES(8,'2021_10_23_082414_create_currencies_table',1);
INSERT INTO migrations VALUES(9,'2022_01_22_034939_create_languages_table',1);
INSERT INTO migrations VALUES(10,'2024_01_01_000001_create_schedules_table',1);
INSERT INTO migrations VALUES(11,'2024_01_01_000002_create_schedule_periods_table',1);
INSERT INTO migrations VALUES(12,'2024_01_01_000003_add_schedule_type_to_schedules_table',1);
INSERT INTO migrations VALUES(13,'2024_01_15_000001_create_ocr_templates_table',1);
INSERT INTO migrations VALUES(14,'2024_01_15_000002_create_ocr_template_fields_table',1);
INSERT INTO migrations VALUES(15,'2024_01_15_000003_create_ocr_documents_table',1);
INSERT INTO migrations VALUES(16,'2024_08_23_110718_create_teams_table',1);
INSERT INTO migrations VALUES(17,'2024_08_23_110719_create_team_user_table',1);
INSERT INTO migrations VALUES(18,'2024_08_23_110720_create_team_invitations_table',1);
INSERT INTO migrations VALUES(19,'2024_08_24_133803_create_companies_table',1);
INSERT INTO migrations VALUES(20,'2024_09_11_114549_create_tasks_table',1);
INSERT INTO migrations VALUES(21,'2024_09_21_112229_create_reactions_table',1);
INSERT INTO migrations VALUES(22,'2024_09_22_084119_create_notes_table',1);
INSERT INTO migrations VALUES(23,'2024_09_22_091034_create_people_table',1);
INSERT INTO migrations VALUES(24,'2024_09_22_092300_create_task_user_table',1);
INSERT INTO migrations VALUES(25,'2024_09_22_110651_add_two_factor_columns_to_users_table',1);
INSERT INTO migrations VALUES(26,'2024_09_22_110718_create_personal_access_tokens_table',1);
INSERT INTO migrations VALUES(27,'2024_09_22_114735_create_opportunities_table',1);
INSERT INTO migrations VALUES(28,'2024_09_26_160649_create_user_social_accounts_table',1);
INSERT INTO migrations VALUES(29,'2024_09_26_170133_create_notifications_table',1);
INSERT INTO migrations VALUES(30,'2025_01_12_000000_create_model_meta_table',1);
INSERT INTO migrations VALUES(31,'2025_01_12_100000_create_unsplash_assets_table',1);
INSERT INTO migrations VALUES(32,'2025_01_12_100001_create_unsplashables_table',1);
INSERT INTO migrations VALUES(33,'2025_02_07_192236_create_custom_fields_table',1);
INSERT INTO migrations VALUES(34,'2025_03_15_180559_create_taskables_table',1);
INSERT INTO migrations VALUES(35,'2025_03_15_192334_create_notables_table',1);
INSERT INTO migrations VALUES(36,'2025_03_17_180206_create_media_table',1);
INSERT INTO migrations VALUES(37,'2025_04_30_143551_add_creation_source_to_entity_tables',1);
INSERT INTO migrations VALUES(38,'2025_05_08_112613_create_imports_table',1);
INSERT INTO migrations VALUES(39,'2025_05_08_112614_create_exports_table',1);
INSERT INTO migrations VALUES(40,'2025_05_08_112615_create_failed_import_rows_table',1);
INSERT INTO migrations VALUES(41,'2025_05_30_000000_create_taxonomies_tables',1);
INSERT INTO migrations VALUES(42,'2025_05_30_010000_migrate_legacy_taxonomies',1);
INSERT INTO migrations VALUES(43,'2025_07_05_093310_update_opportunity_amount_field_type_to_currency',1);
INSERT INTO migrations VALUES(44,'2025_08_25_173222_update_order_column_to_flowforge_position_for_tasks_and_opportunities',1);
INSERT INTO migrations VALUES(45,'2025_08_26_124042_create_system_administrators_table',1);
INSERT INTO migrations VALUES(46,'2025_09_01_000000_add_task_management_features',1);
INSERT INTO migrations VALUES(47,'2025_09_01_000001_align_task_status_options',1);
INSERT INTO migrations VALUES(48,'2025_12_06_050246_create_accounts_table',1);
INSERT INTO migrations VALUES(49,'2025_12_06_052111_add_suitecrm_contact_fields_to_people_table',1);
INSERT INTO migrations VALUES(50,'2025_12_06_052117_add_suitecrm_contact_fields_to_people_table',1);
INSERT INTO migrations VALUES(51,'2025_12_06_062224_add_account_fields_to_companies_table',1);
INSERT INTO migrations VALUES(52,'2025_12_06_062225_create_account_merges_table',1);
INSERT INTO migrations VALUES(53,'2025_12_07_000000_add_company_information_fields',1);
INSERT INTO migrations VALUES(54,'2025_12_07_010000_create_account_team_members_table',1);
INSERT INTO migrations VALUES(55,'2025_12_07_010000_create_company_revenues_table',1);
INSERT INTO migrations VALUES(56,'2025_12_07_010000_create_leads_table',1);
INSERT INTO migrations VALUES(57,'2025_12_07_013048_create_telescope_entries_table',1);
INSERT INTO migrations VALUES(58,'2025_12_07_065939_update_accounts_table_add_missing_columns_and_types',1);
INSERT INTO migrations VALUES(59,'2025_12_07_213757_add_timezone_column_to_users_table',1);
INSERT INTO migrations VALUES(60,'2025_12_07_213855_create_notable_table',1);
INSERT INTO migrations VALUES(61,'2025_12_07_223058_create_features_table',1);
INSERT INTO migrations VALUES(62,'2025_12_07_223102_create_filament_feature_flags_table',1);
INSERT INTO migrations VALUES(63,'2025_12_08_000000_create_unsplash_tables',1);
INSERT INTO migrations VALUES(64,'2025_12_08_000001_create_opportunity_user_table',1);
INSERT INTO migrations VALUES(65,'2025_12_08_002857_create_languages_table',1);
INSERT INTO migrations VALUES(66,'2025_12_08_002858_create_translations_table',1);
INSERT INTO migrations VALUES(67,'2025_12_08_002859_create_translation_files_table',1);
INSERT INTO migrations VALUES(68,'2025_12_08_002900_create_phrases_table',1);
INSERT INTO migrations VALUES(69,'2025_12_08_002901_create_contributors_table',1);
INSERT INTO migrations VALUES(70,'2025_12_08_002903_create_invites_table',1);
INSERT INTO migrations VALUES(71,'2025_12_08_002904_add_is_root_to_translation_files_table',1);
INSERT INTO migrations VALUES(72,'2025_12_08_003343_optimize_database_settings',1);
INSERT INTO migrations VALUES(73,'2025_12_08_005545_add_union_paginator_indexes',1);
INSERT INTO migrations VALUES(74,'2025_12_08_033213_add_world_columns_to_tables',1);
INSERT INTO migrations VALUES(75,'2025_12_08_045900_add_code_coverage_permissions',1);
INSERT INTO migrations VALUES(76,'2025_12_09_191910_create_share_links_table',1);
INSERT INTO migrations VALUES(77,'2025_12_09_222305_create_db_config_table',1);
INSERT INTO migrations VALUES(78,'2025_12_09_232009_add_tenant_aware_column_to_media_table',1);
INSERT INTO migrations VALUES(79,'2025_12_10_120000_add_note_metadata_and_history',1);
INSERT INTO migrations VALUES(80,'2025_12_15_120000_create_support_cases_table',1);
INSERT INTO migrations VALUES(81,'2025_12_15_120001_add_account_and_escalation_fields_to_cases',1);
INSERT INTO migrations VALUES(82,'2025_12_20_000000_create_invoices_table',1);
INSERT INTO migrations VALUES(83,'2025_12_20_000001_add_flowforge_position_to_leads_table',1);
INSERT INTO migrations VALUES(84,'2025_12_20_000001_create_invoice_support_tables',1);
INSERT INTO migrations VALUES(85,'2025_12_20_000002_create_customers_view',1);
INSERT INTO migrations VALUES(86,'2025_12_20_000003_create_tags_table',1);
INSERT INTO migrations VALUES(87,'2025_12_20_000004_create_activities_table',1);
INSERT INTO migrations VALUES(88,'2025_12_20_000006_create_sales_documents',1);
INSERT INTO migrations VALUES(89,'2025_12_20_000011_create_quote_support_tables',1);
INSERT INTO migrations VALUES(90,'2025_12_21_000001_create_order_line_items_table',1);
INSERT INTO migrations VALUES(91,'2025_12_21_000002_add_order_line_item_link_to_purchase_order_line_items',1);
INSERT INTO migrations VALUES(92,'2025_12_21_000002_enhance_orders_for_numbering_and_fulfillment',1);
INSERT INTO migrations VALUES(93,'2025_12_22_000000_create_notification_preferences_table',1);
INSERT INTO migrations VALUES(94,'2025_12_23_000000_create_documents_tables',1);
INSERT INTO migrations VALUES(95,'2025_12_30_000000_create_knowledge_base_tables',1);
INSERT INTO migrations VALUES(96,'2026_01_01_000000_create_process_management_tables',1);
INSERT INTO migrations VALUES(97,'2026_01_02_000000_add_workflow_fields_to_process_definitions_table',1);
INSERT INTO migrations VALUES(98,'2026_01_02_000000_create_extensions_table',1);
INSERT INTO migrations VALUES(99,'2026_01_03_000000_create_pdf_templates_table',1);
INSERT INTO migrations VALUES(100,'2026_01_04_000000_create_territory_management_tables',1);
INSERT INTO migrations VALUES(101,'2026_01_05_000000_create_email_programs_table',1);
INSERT INTO migrations VALUES(102,'2026_01_05_000000_create_product_tables',1);
INSERT INTO migrations VALUES(103,'2026_01_05_000001_add_owner_and_close_fields_to_opportunities',1);
INSERT INTO migrations VALUES(104,'2026_01_05_000001_add_product_inventory_performance_indexes_and_constraints',1);
INSERT INTO migrations VALUES(105,'2026_01_05_000002_create_inventory_adjustments_table',1);
INSERT INTO migrations VALUES(106,'2026_01_10_000000_add_role_to_people_table',1);
INSERT INTO migrations VALUES(107,'2026_01_10_000000_create_settings_table',1);
INSERT INTO migrations VALUES(108,'2026_01_10_000100_create_saved_searches_table',1);
INSERT INTO migrations VALUES(109,'2026_01_10_120010_update_people_phone_numbers_field',1);
INSERT INTO migrations VALUES(110,'2026_01_11_000000_create_calendar_events_table',1);
INSERT INTO migrations VALUES(111,'2026_01_11_000001_add_meeting_fields_to_calendar_events_table',1);
INSERT INTO migrations VALUES(112,'2026_01_11_000002_add_calendar_event_performance_indexes',1);
INSERT INTO migrations VALUES(113,'2026_01_12_000000_add_calendar_events_performance_indexes',1);
INSERT INTO migrations VALUES(114,'2026_01_12_000000_add_product_catalog_enhancements',1);
INSERT INTO migrations VALUES(115,'2026_01_20_000100_create_groups_table',1);
INSERT INTO migrations VALUES(116,'2026_01_20_120000_add_lead_details_fields',1);
INSERT INTO migrations VALUES(117,'2026_02_01_000001_add_addresses_columns',1);
INSERT INTO migrations VALUES(118,'2026_02_10_000000_create_account_contact_pivot_table',1);
INSERT INTO migrations VALUES(119,'2026_02_10_000001_add_account_id_to_opportunities_and_cases',1);
INSERT INTO migrations VALUES(120,'2026_03_01_000000_create_permission_tables',1);
INSERT INTO migrations VALUES(121,'2026_03_02_000000_create_people_emails_table',1);
INSERT INTO migrations VALUES(122,'2026_03_10_000000_create_projects_table',1);
INSERT INTO migrations VALUES(123,'2026_03_10_000001_add_project_schedule_indexes',1);
INSERT INTO migrations VALUES(124,'2026_03_11_000000_enhance_task_engine_features',1);
INSERT INTO migrations VALUES(125,'2026_03_12_000000_create_employees_table',1);
INSERT INTO migrations VALUES(126,'2026_03_20_000000_create_contact_personas_table',1);
INSERT INTO migrations VALUES(127,'2026_03_20_000100_create_contact_roles_table',1);
INSERT INTO migrations VALUES(128,'2026_03_20_000200_create_contact_role_people_table',1);
INSERT INTO migrations VALUES(129,'2026_03_20_000300_create_communication_preferences_table',1);
INSERT INTO migrations VALUES(130,'2026_03_20_000400_create_portal_users_table',1);
INSERT INTO migrations VALUES(131,'2026_03_20_000500_create_contact_merge_logs_table',1);
INSERT INTO migrations VALUES(132,'2026_03_20_000600_add_persona_and_primary_company_to_people_table',1);
INSERT INTO migrations VALUES(133,'2026_03_21_000000_create_model_reference_counters_table',1);
INSERT INTO migrations VALUES(134,'2026_05_27_000000_add_userstamps_columns',1);
INSERT INTO migrations VALUES(135,'2026_05_28_000100_add_zap_schedule_columns_to_calendar_events',1);
INSERT INTO migrations VALUES(136,'2026_06_01_000000_add_cases_performance_indexes',1);
INSERT INTO migrations VALUES(137,'2026_06_09_000000_enhance_quotes_with_addresses_and_owner',1);
INSERT INTO migrations VALUES(138,'create_file_system_items_table',1);
INSERT INTO migrations VALUES(139,'2026_01_13_000000_add_core_crm_data_model_extensions',2);
