# Rosaliza Hotel website architecture sketch

![Rosaliza Hotel website architecture](architecture-sketch.png)

Read the sketch from left to right: users → browser interface → Laravel routes and business logic → shared data or external services. Laravel is one web application. The "Web routes" and "API routes" boxes are two entry-point groups within that application, not separate services.

## API groups shown in the diagram

| Group | Existing examples | Flow and access |
| --- | --- | --- |
| Web (HTML and JSON) | `GET /rooms/search`, `POST /booking`, `GET /payment/check/{bookingId}`, `POST /chatbot/api`, `/staff/*`, `/admin/*` | The browser calls Laravel. Room search is public; booking and payment use guest sessions; internal actions enforce roles or permissions on the server. Responses may be HTML, redirects, or JSON. |
| Payment callbacks | `POST /api/webhook/momo`, `POST /api/webhook/zalopay`, `POST /api/webhook/vnpay` | Payment providers call Laravel, which verifies the result. For VietQR, Laravel queries SePay for reconciliation; this project does not use a SePay webhook. |
| Chatbot tools | `POST /api/chatbot/tools/room-types`, `POST /api/chatbot/tools/rooms/search` | Dify calls Laravel with a separate bearer secret to read public room information. Dify does not query MySQL directly. |
| Device API | `GET/POST /api/iot/rooms/{roomNumber}/cleaning-request` | Devices read or update cleaning requests with `X-API-Key`; the server checks both the key and authorized room. |

Google OAuth uses browser redirects, and email is sent through SMTP. Face ID uses `/staff/face-id/*` routes and a separate sync flow, rather than the cleaning-request IoT API. Laravel's scheduler handles expired holds, no-shows, and Face ID sync. External integrations work only when configured for the environment.

Editable vector source: [architecture-sketch.svg](architecture-sketch.svg).

The layout follows the [C4 container diagram](https://c4model.com/diagrams/container) principle of showing the system boundary, major parts, data, and communication direction. The hand-drawn style and palette follow the project's visual reference.
