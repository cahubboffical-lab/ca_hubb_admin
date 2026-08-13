# Role Management Permission Matrix

This document records the permission contract for the modules covered by the 12 August 2026 QA report. The server is the enforcement boundary; Blade visibility is a matching usability layer, not the security control.

| Module | Permission | Allowed behavior |
| --- | --- | --- |
| Advertisement | `advertisement-list` | Open the module and read advertisement rows |
| Advertisement | `advertisement-update` | Open edit/status actions and submit changes |
| Car Inspection Requests | `car-inspection-request-list` | Open and read request details |
| Car Inspection Requests | `car-inspection-request-update` | Change request workflow status and admin notes |
| Car Inspection Packages | `car-inspection-package-list/create/update/delete` | Perform only the matching package action |
| Sell for Me Requests | `sell-for-me-request-list` | Open and read request details |
| Sell for Me Requests | `sell-for-me-request-update` | Change request workflow status and admin notes |
| Sell for Me Packages | `sell-for-me-package-list/create/update/delete` | Perform only the matching package action |
| News | `news-list/create/update/delete` | Perform only the matching news action |
| Customer | `customer-list` | Open and read the customer table; mutation controls are hidden |
| Customer | `customer-update` | Activate/deactivate customers, change auto-approval, assign packages, view active packages, and cancel packages |

## Enforcement notes

- Staff role changes go through `StaffRoleService`, which uses `syncRoles` so a previous role (including one with broader access) cannot remain attached after reassignment.
- The Customer data endpoint checks Customer permissions separately from Notification recipient permissions.
- Customer package assignment and cancellation require `customer-update` on the server even if a request is submitted without the UI.
- Advertisement navigation uses the current `advertisement-*` names, not the retired `item-*` aliases.
- Request list and update permissions are intentionally separate for Car Inspection and Sell for Me.
- News permissions are inserted by an upgrade migration, so they appear in Role Management on existing installations without rerunning seeders.
- Car Inspection and Sell for Me request/package permissions are also inserted by an upgrade migration. Existing installations therefore receive their List and action options without rerunning seeders.
