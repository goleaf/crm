# Surveys Module

## ADDED Requirements

### Survey creation and design
- The system shall let users create surveys with title, description, availability status, and an ordered set of questions that can be added, reordered, and edited from a design workspace.
#### Scenario: Build a survey draft
- Given a marketer opens the survey builder
- When they enter a survey title, description, add three questions, and reorder question 3 above question 2
- Then the survey saves in draft status with the updated order and editable question list

### Question types (multiple choice, text, rating)
- The survey builder shall support multiple choice (single or multi-select with options), free-text (single-line or long-form), and rating scale questions with configurable scales.
#### Scenario: Add varied question types
- Given a draft survey in the builder
- When the user adds a single-select multiple choice question with four options, a long-text question, and a 1-5 rating question
- Then the survey stores each question with its type, options, and rating scale so the runtime renderer can enforce appropriate inputs

### Survey templates
- Users shall create survey templates that store layout, branding, and starter questions so new surveys can be cloned from a template without overwriting the original.
#### Scenario: Create a survey from a template
- Given a saved survey template with header styling and two base questions
- When a user selects “Create from template” and chooses that template
- Then a new draft survey is created with the template’s styling and questions copied, leaving the template unchanged

### Survey distribution via campaigns
- Surveys shall be distributable through campaigns so campaign messages include personalized survey links tied to the campaign and recipient.
#### Scenario: Attach a survey to a campaign email
- Given a campaign email template and a published survey
- When the marketer inserts the survey link merge tag and sends the campaign
- Then each recipient receives an email with their unique survey link tied to that campaign for response attribution

### Anonymous response options
- Surveys shall support anonymous mode that omits recipient identifiers and PII from stored responses while still allowing aggregate reporting.
#### Scenario: Collect anonymous responses
- Given a survey marked as anonymous
- When external visitors submit responses via the public link
- Then responses are recorded without contact/account linkage or captured identifiers, and reports show aggregate counts only

### Survey scheduling
- Surveys shall support start and end scheduling that controls when links become active and when they close to new responses.
#### Scenario: Enforce survey availability window
- Given a survey scheduled to start tomorrow and end next Friday
- When a recipient tries the link today
- Then the system shows an unavailable message until the start date, allows responses during the window, and closes the survey after the end date

### Response collection
- The system shall record each respondent’s answers per question, preserving timestamps, channel (campaign, embed, direct), and identifiers when not anonymous.
#### Scenario: Store a submitted response
- Given a live survey with three questions
- When a recipient completes all questions via the campaign link
- Then a response record is created with per-question answers, completion timestamp, channel=campaign, and linked recipient metadata

### Survey results analysis
- Users shall view aggregated results including counts for choices, average ratings, and exports of text responses filtered by campaign or time range.
#### Scenario: Analyze results by campaign
- Given responses collected across two campaigns
- When the analyst filters results to Campaign A
- Then the dashboard shows choice distributions and average ratings for Campaign A only, and text responses export with that filter applied

### Response rate tracking
- The system shall calculate response rate by comparing delivered invitations (or link views) to responses started and completed, segmented by campaign.
#### Scenario: View response rates per campaign
- Given 1,000 invitations delivered for Campaign A and 200 responses started with 150 completed
- When the user opens the survey performance view
- Then it shows a 20% start rate and 15% completion rate for Campaign A with counts of delivered, started, and completed

### Survey completion tracking
- Surveys shall track respondent progress and completion status, allowing incomplete responses to be saved and resumed when supported.
#### Scenario: Resume and complete a survey
- Given a respondent answers half the questions and saves progress
- When they return via the same link and finish the remaining questions
- Then the system restores prior answers, records completion, and updates status from in-progress to completed

### Survey logic and branching
- The survey engine shall support conditional branching to skip or show questions based on prior answers, ensuring skipped questions are marked accordingly in stored responses.
#### Scenario: Branch based on a qualifying answer
- Given a survey with a qualifying yes/no question followed by two conditional questions shown only when the answer is Yes
- When a respondent answers No
- Then the conditional questions are skipped, the respondent moves to the next section, and the stored response records those questions as skipped by logic

### Required question settings
- Authors shall be able to mark questions as required so respondents cannot submit without answering them unless the question was skipped by branching.
#### Scenario: Validate required questions
- Given a survey with two required questions and one optional
- When a respondent tries to submit without answering a required question
- Then the system highlights the missing answer, blocks submission, and only allows completion once required responses are provided or skipped by logic

### Survey preview
- The builder shall provide a preview mode that renders the survey with its logic, required rules, and styling without recording responses.
#### Scenario: Preview before publishing
- Given a draft survey with branching and required fields
- When the author clicks Preview and navigates through sample answers
- Then the survey renders as respondents will see it, showing/hiding questions per logic, enforcing required prompts, and no responses are stored

### Survey URL generation
- Surveys shall generate unique, secure URLs per survey and optionally per recipient to support personalized tracking and anonymous public links.
#### Scenario: Generate personalized and public links
- Given a published survey
- When the marketer requests a public link and also inserts the personalized link placeholder in a campaign
- Then the system produces a public URL that any visitor can use and personalized URLs for each campaign recipient that carry tracking tokens

### Survey embedding
- The system shall provide an embeddable widget or iframe code that renders the survey within external sites or portals while honoring anonymous and authenticated modes.
#### Scenario: Embed a survey on a landing page
- Given a live survey with anonymous responses enabled
- When a web admin pastes the embed code into a landing page
- Then the survey renders inline on that page, accepts responses without forcing navigation away, and records them as anonymous embed submissions
