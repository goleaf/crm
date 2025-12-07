# User Management

## ADDED Requirements

### User creation captures core profile details
- Administrators shall create user accounts by entering name, unique email/username, optional job details (title, department, manager), contact numbers, assigning team/role, and setting an invite or temporary password; the system validates uniqueness and records creator and timestamps.
#### Scenario: Create a user with team and initial status
- Given an administrator opens the new user form and enters name, email, title, mobile number, selects Team = Sales with role = Member, and chooses to send an invitation
- When they save the form
- Then the user record is created as Active with the provided profile data, uniqueness validated, creator/timestamps stored, and an invitation is issued to the new user

### User profile information is editable with audit history
- Authorized users shall update profile information (name, title, department, manager, phone numbers, location, pronouns, links) while the system timestamps the change, records the actor, and keeps an audit trail of prior values.
#### Scenario: Update contact info and job details
- Given a user profile lists title = "BDR" and phone = "555-1111"
- When the owner or an administrator edits the profile to title = "Account Executive", department = "Sales", phone = "555-2222", and adds manager = "Dana Lee"
- Then the profile displays the new values, the change is timestamped with the actor recorded, and the previous title/phone remain visible in the audit history

### User activation/deactivation controls access
- The system shall support activating or deactivating a user with a required reason and effective timestamp, preventing authentication and new session issuance for inactive users while preserving record ownership and assignment history.
#### Scenario: Deactivate a departing user
- Given a user is Active
- When an administrator sets status = Inactive with reason "Departed company" effective immediately
- Then the user cannot log in or receive new sessions, the profile shows inactive status with the reason and timestamp, and existing record assignments remain but reflect the inactive badge

### User activity tracking logs authentication and profile events
- User activity logs shall capture logins (success/failure), password resets, profile edits, preference changes, and activation toggles with actor, target user, timestamp, and source IP/device when available, and surface these logs on the user detail for administrators.
#### Scenario: Review a user's recent activity
- Given a user logged in from IP `203.0.113.10`, changed their phone number, and was later deactivated by an admin
- When an administrator opens the user's activity timeline
- Then entries show the successful login with IP/device, the profile update with field-level details, and the deactivation event with actor/time/reason

### User preferences personalize the application experience
- Users shall set personal preferences including timezone, locale, notification channels/frequency (email, mobile, in-app), accessibility options (reduced motion, contrast), and default landing page; these preferences are stored per user and applied to UI rendering, scheduling, and notification delivery.
#### Scenario: Set timezone and notification preferences
- Given a user opens Settings > Preferences
- When they select timezone = "America/New_York", locale = "en-US", enable email digests weekly, disable SMS, and turn on reduced motion
- Then the preferences save to the user's profile, future timestamps display in Eastern time, notification jobs honor the email weekly digest and skip SMS, and UI animations respect reduced motion

### Profile customization supports avatars and presentation details
- Users shall customize their profile appearance by uploading an avatar/photo, setting display name, bio/pronouns, and choosing a default landing page or theme; the system stores the assets safely, enforces size/type validation, and reflects the personalized data across profile views and mentions.
#### Scenario: Customize avatar and profile presentation
- Given a user has the default avatar and no bio
- When they upload a new photo within size/type limits, set display name = "Sam Rivera", add bio = "Enterprise AE • They/Them", and choose landing page = "Tasks"
- Then the profile and mentions show the new avatar, display name, and bio, and after logout/login the user lands on the Tasks page by default
