# Krayin Lead Creation

Source: [Krayin User Documentation - Create Leads in Krayin](https://docs.krayincrm.com/2.x/lead/leads.html#create-leads-in-krayin)

## Overview
- Krayin treats a lead as a potential customer; the record stores company, contact, and pipeline context.
- This summary covers manual creation, Magic AI document upload, and the available lead filters.

## Manual lead creation (UI flow)
1. Go to the Krayin admin panel and choose `Leads >> Create Lead`.
2. Fill lead details:
   - Title
   - Description (opportunity context)
   - Lead Value (estimated amount)
   - Source (Email, Web, Phone, etc.)
   - Type
   - Sales Owner
   - Expected Close Date
3. Add contact person information:
   - Name
   - Email
   - Contact Number
   - Organization
4. Add product line items:
   - Product name
   - Price
   - Quantity
   - Total Amount
5. Click **Save as Lead** to create the record; a pipeline card is generated and can be moved between stages.

## Magic AI document upload
- Navigate to `Leads >> All Leads` and click **Upload File**.
- Select a `.doc`, `.pdf`, or image file that contains lead details; Magic AI extracts Name, Email, Phone, and Organization and creates the lead automatically.
- Uploaded leads land in the **New** stage of the pipeline.
- Magic AI and DOC Generation must be enabled in `Settings >> Configuration >> Magic AI`.

## Lead filtering
- The **Filter** tab supports searching by: ID, Lead Value, Sales Person, Contact Person, Lead Type, Source, Tags, Expected Close Date, and Created At.

## Use in this project
- Reference this flow when aligning our lead capture UX and pipeline defaults, especially if we add document-driven intake similar to Magic AI.
