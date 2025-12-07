<?php

declare(strict_types=1);

use Relaticle\Documentation\Data\DocumentData;

describe('SuiteCRM feature catalog documentation', function () {
    it('renders the feature catalog with anchors for each category', function () {
        $document = DocumentData::fromType('suitecrm-features');

        $expectedTableOfContents = [
            '1-core-crm-modules' => '1. Core CRM Modules',
            '2-sales-and-revenue-management' => '2. Sales and Revenue Management',
            '3-marketing-and-campaign-management' => '3. Marketing and Campaign Management',
            '4-communication-and-collaboration' => '4. Communication and Collaboration',
            '5-project-and-resource-management' => '5. Project and Resource Management',
            '6-knowledge-and-document-management' => '6. Knowledge and Document Management',
            '7-workflow-and-automation' => '7. Workflow and Automation',
            '8-reporting-and-analytics' => '8. Reporting and Analytics',
            '9-customization-and-administration' => '9. Customization and Administration',
            '10-integration-and-api' => '10. Integration and API',
            '11-mobile-and-portal' => '11. Mobile and Portal',
            '12-data-management' => '12. Data Management',
            '13-user-interface-and-experience' => '13. User Interface and Experience',
            '14-system-and-technical' => '14. System and Technical',
            '15-advanced-features' => '15. Advanced Features',
        ];

        expect($document->title)->toBe('SuiteCRM Feature Catalog');
        expect($document->tableOfContents)->toEqual($expectedTableOfContents);
        expect($document->tableOfContents)->toHaveCount(15);
        expect($document->content)
            ->toContain('Comprehensive SuiteCRM capabilities organized by category and subfeature.')
            ->toContain('Advanced Email Features')
            ->toContain('Email deliverability optimization');
    });
});
