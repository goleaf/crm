<?php

declare(strict_types=1);

return [
    'workflow_trigger_type' => [
        'on_create' => 'On Create',
        'on_edit' => 'On Edit',
        'after_save' => 'After Save',
        'scheduled' => 'Scheduled',
    ],

    'workflow_condition_operator' => [
        'equals' => 'Equals',
        'not_equals' => 'Not Equals',
        'greater_than' => 'Greater Than',
        'less_than' => 'Less Than',
        'greater_than_or_equal' => 'Greater Than or Equal',
        'less_than_or_equal' => 'Less Than or Equal',
        'contains' => 'Contains',
        'not_contains' => 'Does Not Contain',
        'starts_with' => 'Starts With',
        'ends_with' => 'Ends With',
        'is_empty' => 'Is Empty',
        'is_not_empty' => 'Is Not Empty',
        'in' => 'In',
        'not_in' => 'Not In',
        'between' => 'Between',
        'changed' => 'Changed',
        'not_changed' => 'Not Changed',
    ],

    'workflow_condition_logic' => [
        'and' => 'AND (All conditions must match)',
        'or' => 'OR (Any condition must match)',
    ],

    'process_status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'inactive' => 'Inactive',
        'archived' => 'Archived',
    ],

    'account_type' => [
        'customer' => 'Customer',
        'prospect' => 'Prospect',
        'partner' => 'Partner',
        'competitor' => 'Competitor',
        'investor' => 'Investor',
        'reseller' => 'Reseller',
    ],

    'account_team_role' => [
        'owner' => 'Owner',
        'account_manager' => 'Account Manager',
        'sales' => 'Sales',
        'customer_success' => 'Customer Success',
        'executive_sponsor' => 'Executive Sponsor',
        'technical_contact' => 'Technical Contact',
        'billing' => 'Billing',
        'support' => 'Support',
    ],

    'account_team_access_level' => [
        'view' => 'View',
        'edit' => 'Edit',
        'manage' => 'Manage',
    ],

    'address_type' => [
        'billing' => 'Billing',
        'shipping' => 'Shipping',
        'headquarters' => 'Headquarters',
        'mailing' => 'Mailing',
        'office' => 'Office',
        'other' => 'Other',
    ],

    'approval_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'article_status' => [
        'draft' => 'Draft',
        'pending_review' => 'Pending Review',
        'published' => 'Published',
        'archived' => 'Archived',
    ],

    'article_visibility' => [
        'public' => 'Public',
        'internal' => 'Internal',
        'restricted' => 'Restricted',
    ],

    'bounce_type' => [
        'hard' => 'Hard Bounce',
        'soft' => 'Soft Bounce',
        'complaint' => 'Complaint',
    ],

    'calendar_event_status' => [
        'scheduled' => 'Scheduled',
        'confirmed' => 'Confirmed',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'calendar_event_type' => [
        'meeting' => 'Meeting',
        'call' => 'Call',
        'demo' => 'Demo',
        'follow_up' => 'Follow Up',
        'other' => 'Other',
    ],

    'calendar_sync_status' => [
        'not_synced' => 'Not Synced',
        'synced' => 'Synced',
        'failed' => 'Failed',
    ],

    'case_channel' => [
        'email' => 'Email',
        'portal' => 'Portal',
        'phone' => 'Phone',
        'chat' => 'Chat',
        'internal' => 'Internal',
    ],

    'case_priority' => [
        'p1' => 'P1 - Critical',
        'p2' => 'P2 - High',
        'p3' => 'P3 - Medium',
        'p4' => 'P4 - Low',
    ],

    'case_status' => [
        'new' => 'New',
        'assigned' => 'Assigned',
        'pending_input' => 'Pending Input',
        'closed' => 'Closed',
    ],

    'case_type' => [
        'question' => 'Question',
        'problem' => 'Problem',
        'incident' => 'Incident',
        'request' => 'Request',
    ],

    'comment_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'hidden' => 'Hidden',
    ],

    'contact_email_type' => [
        'work' => 'Work',
        'personal' => 'Personal',
        'other' => 'Other',
    ],

    'creation_source' => [
        'web' => 'Web',
        'import' => 'Import',
        'system' => 'System',
    ],

    'delivery_address_type' => [
        'origin' => 'Origin',
        'destination' => 'Destination',
        'pickup' => 'Pickup',
        'drop_off' => 'Drop Off',
        'return' => 'Return',
        'other' => 'Other',
    ],

    'delivery_status' => [
        'pending' => 'Pending',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
    ],

    'employee_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'on_leave' => 'On Leave',
        'terminated' => 'Terminated',
    ],

    'extension_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
        'disabled' => 'Disabled',
        'failed' => 'Failed',
    ],

    'extension_type' => [
        'logic_hook' => 'Logic Hook',
        'entry_point' => 'Entry Point',
        'scheduler' => 'Scheduler',
        'module' => 'Module',
        'relationship' => 'Relationship',
        'vardef' => 'Vardef',
        'metadata' => 'Metadata',
        'language' => 'Language',
        'view' => 'View',
        'controller' => 'Controller',
        'dashlet' => 'Dashlet',
        'calculation' => 'Calculation',
    ],

    'faq_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
    ],

    'hook_event' => [
        'before_save' => 'Before Save',
        'after_save' => 'After Save',
        'before_delete' => 'Before Delete',
        'after_delete' => 'After Delete',
        'before_retrieve' => 'Before Retrieve',
        'after_retrieve' => 'After Retrieve',
        'before_relationship' => 'Before Relationship',
        'after_relationship' => 'After Relationship',
        'process_record' => 'Process Record',
    ],

    'industry' => [
        'agriculture' => 'Agriculture',
        'automotive' => 'Automotive',
        'construction' => 'Construction',
        'consulting' => 'Consulting',
        'education' => 'Education',
        'energy' => 'Energy',
        'finance' => 'Finance',
        'government' => 'Government',
        'healthcare' => 'Healthcare',
        'hospitality' => 'Hospitality',
        'insurance' => 'Insurance',
        'logistics' => 'Logistics',
        'manufacturing' => 'Manufacturing',
        'media' => 'Media',
        'non_profit' => 'Non-Profit',
        'professional_services' => 'Professional Services',
        'real_estate' => 'Real Estate',
        'renewable_energy' => 'Renewable Energy',
        'retail' => 'Retail',
        'technology' => 'Technology',
        'telecommunications' => 'Telecommunications',
        'transportation' => 'Transportation',
        'other' => 'Other',
    ],

    'invoice_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
        'cancelled' => 'Cancelled',
    ],

    'invoice_payment_status' => [
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],

    'invoice_recurrence_frequency' => [
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
        'quarterly' => 'Quarterly',
        'yearly' => 'Yearly',
    ],

    'invoice_reminder_type' => [
        'due_soon' => 'Due Soon',
        'overdue' => 'Overdue',
        'custom' => 'Custom',
    ],

    'lead_status' => [
        'new' => 'New',
        'working' => 'Working',
        'nurturing' => 'Nurturing',
        'qualified' => 'Qualified',
        'unqualified' => 'Unqualified',
        'converted' => 'Converted',
        'recycled' => 'Recycled',
    ],

    'lead_grade' => [
        'a' => 'A',
        'b' => 'B',
        'c' => 'C',
        'd' => 'D',
        'e' => 'E',
        'f' => 'F',
        'unrated' => 'Unrated',
    ],

    'lead_nurture_status' => [
        'not_started' => 'Not Started',
        'active' => 'Active',
        'paused' => 'Paused',
        'completed' => 'Completed',
    ],

    'lead_source' => [
        'website' => 'Website',
        'web_form' => 'Web Form',
        'referral' => 'Referral',
        'partner' => 'Partner',
        'campaign' => 'Campaign',
        'event' => 'Event',
        'advertising' => 'Advertising',
        'social' => 'Social Media',
        'email' => 'Email',
        'cold_call' => 'Cold Call',
        'outbound' => 'Outbound',
        'import' => 'Import',
        'other' => 'Other',
    ],

    'lead_assignment_strategy' => [
        'manual' => 'Manual',
        'round_robin' => 'Round Robin',
        'territory' => 'Territory',
        'rule_based' => 'Rule Based',
        'weighted' => 'Weighted',
    ],

    'note_category' => [
        'general' => 'General',
        'call' => 'Call',
        'meeting' => 'Meeting',
        'email' => 'Email',
        'follow_up' => 'Follow Up',
        'support' => 'Support',
        'task' => 'Task',
        'other' => 'Other',
    ],

    'note_visibility' => [
        'internal' => 'Internal',
        'external' => 'External',
        'private' => 'Private',
    ],

    'note_history_event' => [
        'created' => 'Created',
        'updated' => 'Updated',
        'deleted' => 'Deleted',
    ],

    'order_status' => [
        'draft' => 'Draft',
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'fulfilled' => 'Fulfilled',
        'cancelled' => 'Cancelled',
    ],

    'order_fulfillment_status' => [
        'pending' => 'Pending',
        'partial' => 'Partial',
        'fulfilled' => 'Fulfilled',
    ],

    'pdf_generation_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'completed' => 'Completed',
        'failed' => 'Failed',
    ],

    'pdf_template_status' => [
        'draft' => 'Draft',
        'active' => 'Active',
        'archived' => 'Archived',
    ],

    'process_approval_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'escalated' => 'Escalated',
    ],

    'process_event_type' => [
        'execution_started' => 'Execution Started',
        'execution_completed' => 'Execution Completed',
        'execution_failed' => 'Execution Failed',
        'execution_cancelled' => 'Execution Cancelled',
        'step_started' => 'Step Started',
        'step_completed' => 'Step Completed',
        'step_failed' => 'Step Failed',
        'approval_requested' => 'Approval Requested',
        'approval_granted' => 'Approval Granted',
        'approval_rejected' => 'Approval Rejected',
        'escalation_triggered' => 'Escalation Triggered',
        'sla_breached' => 'SLA Breached',
        'rollback_initiated' => 'Rollback Initiated',
        'rollback_completed' => 'Rollback Completed',
    ],

    'process_execution_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'cancelled' => 'Cancelled',
        'awaiting_approval' => 'Awaiting Approval',
        'escalated' => 'Escalated',
        'rolled_back' => 'Rolled Back',
    ],

    'process_step_status' => [
        'pending' => 'Pending',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'skipped' => 'Skipped',
    ],

    'project_status' => [
        'planning' => 'Planning',
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
    ],

    'purchase_order_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ],

    'purchase_order_receipt_type' => [
        'receipt' => 'Receipt',
        'return' => 'Return',
    ],

    'quote_status' => [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'accepted' => 'Accepted',
        'rejected' => 'Rejected',
    ],

    'quote_discount_type' => [
        'percent' => 'Percent',
        'fixed' => 'Fixed Amount',
    ],

    'territory_role' => [
        'owner' => 'Owner',
        'member' => 'Member',
        'viewer' => 'Viewer',
    ],

    'territory_type' => [
        'geographic' => 'Geographic',
        'product' => 'Product',
        'hybrid' => 'Hybrid',
    ],

    'territory_overlap_resolution' => [
        'priority' => 'Priority',
        'split' => 'Split',
        'manual' => 'Manual',
    ],

    'time_off_status' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'cancelled' => 'Cancelled',
    ],

    'time_off_type' => [
        'vacation' => 'Vacation',
        'sick' => 'Sick Leave',
        'personal' => 'Personal',
        'bereavement' => 'Bereavement',
        'parental' => 'Parental Leave',
        'unpaid' => 'Unpaid Leave',
    ],

    'vendor_status' => [
        'active' => 'Active',
        'on_hold' => 'On Hold',
        'inactive' => 'Inactive',
    ],

    // Custom Field Translations
    'company_field' => [
        'domain_name' => 'Domain Name',
        'domain_name_description' => 'Company website domain',
        'icp' => 'Ideal Customer Profile',
        'icp_description' => 'Whether this company matches ICP criteria',
        'linkedin' => 'LinkedIn URL',
        'linkedin_description' => 'Company LinkedIn profile',
    ],

    'note_field' => [
        'body' => 'Note Body',
    ],

    'opportunity_field' => [
        'amount' => 'Amount',
        'close_date' => 'Close Date',
        'stage' => 'Stage',
        'probability' => 'Probability',
        'probability_description' => 'Likelihood of closing (%)',
        'next_steps' => 'Next Steps',
        'next_steps_description' => 'Planned actions to move forward',
        'competitors' => 'Competitors',
        'competitors_description' => 'Competing vendors or solutions',
        'forecast_category' => 'Forecast Category',
        'forecast_category_description' => 'Revenue forecast classification',
        'outcome_notes' => 'Outcome Notes',
        'outcome_notes_description' => 'Final outcome details',
        'related_quotes' => 'Related Quotes',
        'related_quotes_description' => 'Associated quote documents',
    ],

    'people_field' => [
        'job_title' => 'Job Title',
        'phone_number' => 'Phone Number',
        'emails' => 'Email Addresses',
        'linkedin' => 'LinkedIn URL',
    ],

    'task_field' => [
        'description' => 'Description',
        'description_description' => 'Task details and requirements',
        'due_date' => 'Due Date',
        'due_date_description' => 'When the task should be completed',
        'priority' => 'Priority',
        'priority_description' => 'Task urgency level',
        'status' => 'Status',
        'status_description' => 'Current task state',
    ],
];
