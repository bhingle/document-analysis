# 📄 Document Analysis AI System

A Laravel-based REST API for uploading, analyzing, and managing PDF documents using OpenAI and PDF parsing libraries.

---

## 🏗️ Tech Stack

- **PHP version**: 8.2.28
- **Laravel Framework version**: 12.10.2
- **Database**: SQLite
- **OpenAI API**
- **Postman** – API Testing

### Laravel Libraries / Packages

- `Smalot\PdfParser`
- `Http\Request`
---
## 🏗️ System Architecture Diagram

Check out the demo on YouTube:  
[👉 Watch the demo here](https://www.youtube.com/watch?v=fQkxtaQirDo)

![Architecture Diagram](https://github.com/bhingle/document-analysis/blob/main/Architecture%20Diagram.png?raw=true)

This architecture shows a Laravel-based system:

- Users interact via Postman/browser
- Files stored in `/storage/app/private`
- Metadata saved in **SQLite**
- On `/analyze`:
  - Laravel checks **file-based cache** (using `CACHE_DRIVER=file`)
  - If cached → returns cached result
  - If not cached → calls **OpenAI API** (API key from `.env`), saves response to **cache + DB**, returns to client

---
## 📝 API Endpoints

Download the Postman Collection: [Download Collection](https://github.com/bhingle/document-analysis/blob/main/Document%20Analysis.postman_collection.json)

| Endpoint                         | Method | Controller Method                     | Description                                 | Request Body / Params                                                       | Response Example |
|---------------------------------|--------|--------------------------------------|---------------------------------------------|-----------------------------------------------------------------------------|-----------------|
| `/register`                      | POST   | `RegisteredUserController@store`      | Registers a new user.                       | `{ "name": "testuser48", "email": "test48@example.com", "password": "password123", "password_confirmation": "password123" }` | `{ "message": "User registered successfully!" }` OR `{ "message": "User already registered with this email." }` |
| `/login`                         | POST   | `AuthenticatedSessionController@store`| Logs in a user and starts a session.        | `{ "email": "test48@example.com", "password": "password123" }`              | `{ "message": "Login successful." }` |
| `/logout`                        | POST   | `AuthenticatedSessionController@destroy` | Logs out the authenticated user.            | None                                                                        | `{ "message": "Logout successful!" }` |
| `/documents`                     | POST   | `DocumentController@store`            | Uploads a new document for the authenticated user. | `form-data` (file or text)                                                  | ```json { "message": "Document uploaded successfully!", "document": { "user_id": 17, "filename": "documents/glHzl9MdEHjZGVjKXLAVwbAmwDs5j1gJpGBbTZyh.pdf", "original_name": "Sample_Service_Contract.pdf", "status": "pending", "updated_at": "2025-05-03T17:51:30.000000Z", "created_at": "2025-05-03T17:51:30.000000Z", "id": 23 } } ``` |
| `/documents`                     | GET    | `DocumentController@index`            | Lists all uploaded documents of the authenticated user. | None <br> URL: `http://127.0.0.1:8000/api/documents`                      | ```json { "documents": [ { "id": 23, "original_name": "Sample_Service_Contract.pdf", "uploaded_at": "2025-05-03T17:51:30.000000Z", "url": "/storage/documents/glHzl9MdEHjZGVjKXLAVwbAmwDs5j1gJpGBbTZyh.pdf" } ] } ``` |
| `/documents/{document}`          | DELETE | `DocumentController@destroy`          | Deletes a specific document.                 | `{document}` → Document ID in URL path <br> Example: `/api/documents/23`    | `{ "message": "Document deleted successfully." }` |
| `/documents/{document}/download` | GET    | `DocumentController@download`         | Downloads the original uploaded document.    | `{document}` → Document ID in URL path <br> Example: `/api/documents/24/download` | **PDF file download** |
| `/documents/{document}/analyze`  | POST   | `DocumentController@analyze`          | Analyzes a specific document using OpenAI and caches result. | `{document}` → Document ID in URL path                                     | [ Sample Analyzed Document via API](https://docs.google.com/document/d/1gVhWIIIlvtY6aWSQ93RfC79gq0wspIfRP4iagEeLp6o/edit?usp=sharing) |
| `/analyzed-documents`            | GET    | `DocumentController@analyzedDocuments`| Retrieves a list of analyzed documents for the authenticated user. | None                                                                        | [All Analyzed Document](https://docs.google.com/document/d/1DT5ch7CkiAYwo-SrFnZPLNI8qWX7mxkCAq5WEltcUP0/edit?usp=sharing) |

---

## 🎯 Overview of Implemented Features

### 🔐 User Authentication & Authorization
- **Endpoints:** `/api/register`, `/api/login`, `/api/logout`
- Protected routes using **auth middleware**
- **Role-based permission system:**
  - **Admin:** Manage all documents
  - **Customer:** Manage only own documents

---

### 📄 Document Upload & Management
- Uploads stored in `/storage/app/private/`
- Metadata saved in **SQLite** (filename, original name, user_id, status)
- Download original document
- Delete document with auth verification

---

### 🧠 Document Analysis with OpenAI API
- Parses uploaded PDF → extracts plain text
- Sends prompt to OpenAI `gpt-3.5-turbo` for analysis
- Saves AI-generated analysis to database (in `analysis` field)
- **Caching:** result cached for 1 hour
- **Logging:** Laravel logs for debugging analysis flow

---

### 🚦 Rate Limiting
- **2 requests per 5 minutes per user** on `/documents/{id}/analyze`
- Implemented using Laravel’s `throttle` middleware
- Protects OpenAI API from abuse/spam

---

### ⚠️ Error Handling & Input Validation
- Logs errors for easier debugging
- Only **Text** and **PDF** allowed for analysis
- Email validation via Laravel
- Password must meet min length (e.g., 8 chars)

---

### 🧪 Testing & Validation
- Manual testing using **Postman** (authenticated calls)
- Unit & Feature tests for document upload, analysis
- Validated with both **Admin** and **Customer** roles
- Confirmed caching → avoids duplicate OpenAI API calls
- Tested download with valid/invalid document IDs

---

## 🧩 Details on AI Integration Strategy

### AI Service Selection
- **Provider:** OpenAI `gpt-3.5-turbo`
- Integrated using `Http::withHeaders()->post()` in Laravel
- **Prompt dynamically built** per document:
  - Injects extracted PDF text
  - Requests AI to extract:
    - Key sections
    - Critical items
    - Defined terms
    - Obligations

---

### AI Request Workflow
1. Extract PDF text → `smalot/pdfparser`
2. Build structured prompt
3. Send prompt → OpenAI Chat Completions API
4. Parse JSON AI response
5. Save structured result in DB
6. Cache result for 1 hour

---

### 🏎️ Performance & Cost Optimizations
- Cached analysis → avoids repeated API calls
- Rate limiting → prevents abuse + controls API quota
- Store AI result in DB → future reads come from DB/cache

---

## 🔑 Key Assumptions & Design Trade-offs

### ✅ Key Assumptions
- Exactly **two user roles**: admin & customer
- Admin → access all documents
- Customer → access only own documents
- One analysis per document per call (unless cached)

---

### ⚖️ Design Trade-offs
- **Synchronous** analysis (no queue/job dispatch)
  - Simpler but risk of API timeout on large docs
- **No auto file cleanup**
  - Deletes only via explicit user action
  - Trade-off: avoids accidental deletions, but manual cleanup required

---

## 💡 Recommendations for Future Enhancements

- Tiered caching → cache high-demand analyses longer
- **JWT-based authentication**
- **Two-Factor Authentication (2FA)** via email/SMS
- Per-document access controls → role-based analysis visibility
- Interactive frontend GUI for backend
- Migrate to **cloud database & deployment**
- Add **queue system** for large doc processing

---

## 🙌 Contributing

Feel free to fork, raise issues, or submit pull requests!

