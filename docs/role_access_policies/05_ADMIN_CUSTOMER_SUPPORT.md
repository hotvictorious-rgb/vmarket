# Role Security & Endpoint Access Policy: Admin Staff: Customer Support Specialist

> **Role Scope:** `Admin Staff: Customer Support Specialist`  
> **Total Allowed Endpoints:** `30`  
> **Total Disallowed / Blocked Endpoints:** `1553`  
> **Security Compliance:** Universal 5-Pillar Security Standard (Zero-Trust, Scoped Isolation)  

## 1. Role Overview & Architectural Boundaries

Super Admin employee assigned to helpdesk tickets, dispute resolution, and live customer inquiries.

### Core Authorized Capabilities:
- ✅ **Live Chat and Customer Ticket Resolution**
- ✅ **WhatsApp CRM Messaging and Conversation Reassignment**
- ✅ **Order Status Inquiries and General Customer Guidance**

### Strict Architectural Restrictions:
- ⛔ **Blocked from approving withdrawals, issuing manual bank disbursements, or modifying system settings**
- ⛔ **Blocked from approving vendor marketplace applications**

---

## 2. Authorized Endpoints Access Matrix (30 Endpoints)

| # | Method | URI | Route Name | Action / Controller |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/admin/whatsapp-crm` | `admin.whatsapp-crm.index` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppCrmController@index` |
| 2 | `GET` | `/admin/whatsapp-crm/messages/{id}` | `admin.whatsapp-crm.get-messages` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppCrmController@getMessages` |
| 3 | `POST` | `/admin/whatsapp-crm/send/{id}` | `admin.whatsapp-crm.send-message` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppCrmController@sendMessage` |
| 4 | `POST` | `/admin/whatsapp-crm/status/{id}` | `admin.whatsapp-crm.update-status` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppCrmController@updateStatus` |
| 5 | `POST` | `/admin/whatsapp-crm/reassign/{id}` | `admin.whatsapp-crm.reassign` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppCrmController@reassignAgent` |
| 6 | `GET` | `/admin/whatsapp-crm/broadcasts` | `admin.whatsapp-crm.broadcasts` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppBroadcastController@index` |
| 7 | `POST` | `/admin/whatsapp-crm/broadcasts/store` | `admin.whatsapp-crm.broadcasts.store` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppBroadcastController@store` |
| 8 | `GET` | `/admin/whatsapp-crm/ai-settings` | `admin.whatsapp-crm.ai-settings` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppAiSettingsController@index` |
| 9 | `POST` | `/admin/whatsapp-crm/ai-settings/faq` | `admin.whatsapp-crm.ai-settings.faq-store` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppAiSettingsController@storeFaq` |
| 10 | `DELETE` | `/admin/whatsapp-crm/ai-settings/faq/{id}` | `admin.whatsapp-crm.ai-settings.faq-delete` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppAiSettingsController@deleteFaq` |
| 11 | `POST` | `/admin/whatsapp-crm/ai-settings/update` | `admin.whatsapp-crm.ai-settings.update` | `App\Http\Controllers\Admin\WhatsApp\WhatsAppAiSettingsController@updateSettings` |
| 12 | `GET` | `/admin/dashboard` | `admin.dashboard.index` | `App\Http\Controllers\Admin\DashboardController@index` |
| 13 | `POST` | `/admin/dashboard/order-status` | `admin.dashboard.order-status` | `App\Http\Controllers\Admin\DashboardController@getOrderStatus` |
| 14 | `GET` | `/admin/dashboard/earning-statistics` | `admin.dashboard.earning-statistics` | `App\Http\Controllers\Admin\DashboardController@getEarningStatistics` |
| 15 | `GET` | `/admin/dashboard/order-statistics` | `admin.dashboard.order-statistics` | `App\Http\Controllers\Admin\DashboardController@getOrderStatistics` |
| 16 | `GET` | `/admin/dashboard/real-time-activities` | `admin.dashboard.real-time-activities` | `App\Http\Controllers\Admin\DashboardController@getRealTimeActivities` |
| 17 | `GET` | `/admin/support-ticket/view` | `admin.support-ticket.view` | `App\Http\Controllers\Admin\HelpAndSupport\SupportTicketController@index` |
| 18 | `POST` | `/admin/support-ticket/status` | `admin.support-ticket.status` | `App\Http\Controllers\Admin\HelpAndSupport\SupportTicketController@updateStatus` |
| 19 | `GET` | `/admin/support-ticket/single-ticket/{id}` | `admin.support-ticket.singleTicket` | `App\Http\Controllers\Admin\HelpAndSupport\SupportTicketController@getView` |
| 20 | `POST` | `/admin/support-ticket/single-ticket/{id}` | `admin.support-ticket.replay` | `App\Http\Controllers\Admin\HelpAndSupport\SupportTicketController@reply` |
| 21 | `GET` | `/admin/messages/index/{type}` | `admin.messages.index` | `App\Http\Controllers\Admin\ChattingController@index` |
| 22 | `GET` | `/admin/messages/message` | `admin.messages.message` | `App\Http\Controllers\Admin\ChattingController@getMessageByUser` |
| 23 | `POST` | `/admin/messages/message` | `admin.messages.` | `App\Http\Controllers\Admin\ChattingController@addAdminMessage` |
| 24 | `GET` | `/admin/contact/list` | `admin.contact.list` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@index` |
| 25 | `GET` | `/admin/contact/view/{id}` | `admin.contact.view` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@getView` |
| 26 | `POST` | `/admin/contact/filer` | `admin.contact.filter` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@getListByFilter` |
| 27 | `POST` | `/admin/contact/delete` | `admin.contact.delete` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@delete` |
| 28 | `POST` | `/admin/contact/update/{id}` | `admin.contact.update` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@update` |
| 29 | `POST` | `/admin/contact/store` | `admin.contact.store` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@add` |
| 30 | `POST` | `/admin/contact/send-mail/{id}` | `admin.contact.send-mail` | `App\Http\Controllers\Admin\HelpAndSupport\ContactController@sendMail` |

---

## 3. Disallowed & Gated Endpoints Summary (1553 Endpoints Blocked)

Attempting to access any of the 1553 disallowed endpoints will be strictly intercepted by Laravel Route Middleware and Zero-Trust RBAC Guards, returning `HTTP 302 Redirect`, `HTTP 401 Unauthorized`, `HTTP 403 Forbidden`, or `HTTP 404 Not Found`.

### Sample Gated Endpoints for this Role:

| # | Method | Gated URI | Guard Interceptor | Reason for Gating |
|:---:|:---:|---|---|---|
| 1 | `GET` | `/_debugbar/open` | `auth / rbac` | Strictly isolated outside role boundary |
| 2 | `GET` | `/_debugbar/clockwork/{id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 3 | `GET` | `/_debugbar/assets/stylesheets` | `auth / rbac` | Strictly isolated outside role boundary |
| 4 | `GET` | `/_debugbar/assets/javascript` | `auth / rbac` | Strictly isolated outside role boundary |
| 5 | `DELETE` | `/_debugbar/cache/{key}/{tags?}` | `auth / rbac` | Strictly isolated outside role boundary |
| 6 | `POST` | `/_debugbar/queries/explain` | `auth / rbac` | Strictly isolated outside role boundary |
| 7 | `POST` | `/oauth/token` | `auth / rbac` | Strictly isolated outside role boundary |
| 8 | `GET` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 9 | `POST` | `/oauth/token/refresh` | `auth / rbac` | Strictly isolated outside role boundary |
| 10 | `POST` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 11 | `DELETE` | `/oauth/authorize` | `auth / rbac` | Strictly isolated outside role boundary |
| 12 | `GET` | `/oauth/tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 13 | `DELETE` | `/oauth/tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 14 | `GET` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 15 | `POST` | `/oauth/clients` | `auth / rbac` | Strictly isolated outside role boundary |
| 16 | `PUT` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 17 | `DELETE` | `/oauth/clients/{client_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 18 | `GET` | `/oauth/scopes` | `auth / rbac` | Strictly isolated outside role boundary |
| 19 | `GET` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 20 | `POST` | `/oauth/personal-access-tokens` | `auth / rbac` | Strictly isolated outside role boundary |
| 21 | `DELETE` | `/oauth/personal-access-tokens/{token_id}` | `auth / rbac` | Strictly isolated outside role boundary |
| 22 | `GET` | `/sanctum/csrf-cookie` | `auth / rbac` | Strictly isolated outside role boundary |
| 23 | `GET` | `/_ignition/health-check` | `auth / rbac` | Strictly isolated outside role boundary |
| 24 | `POST` | `/_ignition/execute-solution` | `auth / rbac` | Strictly isolated outside role boundary |
| 25 | `POST` | `/_ignition/update-config` | `auth / rbac` | Strictly isolated outside role boundary |


