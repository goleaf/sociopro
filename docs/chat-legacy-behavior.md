# Chat Legacy Behavior

This document captures the current chat/message-thread behavior before the
legacy naming cleanup. The tests intentionally assert the persisted typo names
because they are still part of the live database contract.

## Legacy Storage Names

- Message thread model: `App\Models\MessageThread`
- Message thread table: `message_thrades`
- Chat table: `chats`
- Thread receiver column: `message_thrades.reciver_id`
- Thread center column: `message_thrades.chatcenter`
- Chat thread column: `chats.message_thrade`
- Chat receiver column: `chats.reciver_id`
- Chat center column: `chats.chatcenter`

## Clean Compatibility Columns

The schema now has nullable clean compatibility columns alongside the legacy
columns:

- `message_thrades.receiver_id`
- `message_thrades.chat_center`
- `chats.message_thread_id`
- `chats.receiver_id`
- `chats.chat_center`

The additive migration
`2026_07_04_120000_add_clean_chat_thread_columns_for_legacy_compatibility.php`
backfills clean columns from legacy columns without overwriting existing clean
values. The test dump in `database/schema/install.sql` includes these columns
because the test bootstrap imports the dump as the base schema before feature
tests exercise chat writes.

No table rename has happened yet. The table remains `message_thrades`.

## Dual-Write And Read Fallback

New chat/thread writes populate both legacy and clean columns:

- `reciver_id` and `receiver_id`
- `chatcenter` and `chat_center`
- `message_thrade` and `message_thread_id`

Model accessors and query scopes prefer the clean columns when they are present
and fall back to the legacy columns for older rows. This allows old mobile/API
clients and legacy rows to continue working while new code can use canonical
names internally.

## Internal Refactor Seams

The first behavior-preserving internal cleanup introduced named model constants
for the legacy chat storage contract. New internal chat code should reference
the constants instead of repeating raw column strings:

- `MessageThread::TABLE`
- `MessageThread::SENDER_ID_COLUMN`
- `MessageThread::LEGACY_RECEIVER_ID_COLUMN`
- `MessageThread::RECEIVER_ID_COLUMN`
- `MessageThread::LEGACY_CHAT_CENTER_COLUMN`
- `MessageThread::CHAT_CENTER_COLUMN`
- `Chat::LEGACY_MESSAGE_THREAD_ID_COLUMN`
- `Chat::MESSAGE_THREAD_ID_COLUMN`
- `Chat::SENDER_ID_COLUMN`
- `Chat::LEGACY_RECEIVER_ID_COLUMN`
- `Chat::RECEIVER_ID_COLUMN`
- `Chat::LEGACY_CHAT_CENTER_COLUMN`
- `Chat::CHAT_CENTER_COLUMN`
- `Chat::READ_STATUS_COLUMN`

The models now expose relationship and scope seams for refactors while keeping
legacy persistence unchanged:

- `MessageThread::sender()`, `receiver()`, and `messages()`
- `Chat::messageThread()`, `sender()`, `receiver()`, and `mediaFiles()`
- `MessageThread::betweenUsers(...)` with clean-column preference and
  legacy-column fallback; legacy-compatible
  `betweenParticipants(...)` retained
- `Chat::forThread(...)`, `unreadForReceiver(...)`, and `betweenUsers(...)`
  with clean-column preference and legacy-column fallback; legacy-compatible
  `forMessageThread(...)` and `betweenParticipants(...)` retained

The web chat save path now delegates thread and message persistence to focused
actions:

- `App\Actions\Chat\FindOrCreateMessageThreadAction`
- `App\Actions\Chat\StoreChatMessageAction`

`App\Http\Requests\Chat\StoreChatMessageRequest` accepts current legacy chat
inputs, including `reciver_id`, but intentionally does not tighten attachment
extension handling yet. Attachment upload behavior remains in
`ChatController` until a separate transaction/validation cleanup can preserve
client compatibility.

`Chat` still uses guarded assignment. The current safe seam is
`StoreChatMessageAction`, which writes explicit legacy attributes. Converting
`Chat` to a narrow `$fillable` contract should happen after all legacy chat
write paths are routed through focused actions and covered by tests.

## Web Routes Covered

- `GET /chat/inbox/{receiver}/{product?}` named `chat`
- `POST /chat/save` named `chat.save`
- `GET /chat/inbox/load/data/ajax` named `chat.load`
- `GET /chat/inbox/read/message/ajax` named `chat.read`
- `GET /chat/own/remove/{id}` named `remove.chat`
- `POST /my_message_react` named `react.chat`
- `GET /chat/profile/search` named `search.chat`

The original legacy route variable was `reciver`; the current checkout already
uses `{receiver}` for the web inbox route while keeping legacy request and
storage fields.

`chat.load` and `search.chat` now read query-string values from the Laravel
`Request` object instead of raw PHP superglobals.

`search.chat` keeps the legacy HTML-string response contract, but contact rows
are now rendered through an escaped Blade partial.

## API Routes Covered

- `GET /api/chat` named `api.chat.index`
- `GET /api/chat_msg/{message_thread}` named `api.chat.messages.index`
- `POST /api/chat_save` named `api.chat.messages.store`
- `POST /api/thread_save` named `api.chat.threads.store`
- `POST /api/remove_chat/{chat_id}` named `api.chat.messages.destroy`
- `POST /api/chat_read_option/{user_id}` named `api.chat.read.store`
- `POST /api/react_chat` named `api.chat.reactions.store`

The original legacy route variable was `{msg_thrade}`; the current checkout
already uses `{message_thread}` for the API messages route while preserving the
legacy `message_thrade` payload key.

## Response Shapes Covered

`POST /chat/save` returns a JSON-encoded HTML fragment contract:

- `appendElement`
- `content`
- `clickTo`
- `replaceUrl` and `url` for product chat responses

`GET /chat/inbox/load/data/ajax` returns a JSON-encoded HTML fragment contract:

- `appendElement`
- `content`

`POST /my_message_react` returns a JSON-encoded reaction fragment contract:

- `elemSelector`
- `content`

`GET /chat/profile/search` returns an HTML string, not JSON.

`GET /api/chat` returns an array of chat summary objects with both canonical and
legacy receiver keys:

- `receiver_id`
- `reciver_id`

`GET /api/chat_msg/{message_thread}` returns an array of chat message objects
with both canonical and legacy thread/receiver keys:

- `message_thread_id`
- `message_thrade`
- `receiver_id`
- `reciver_id`

`POST /api/chat_save` returns the same append/click HTML fragment shape for the
first message in a new thread, but currently returns an empty array when reusing
an existing thread.

`POST /api/thread_save` and `POST /api/react_chat` currently return an empty
array on success.

`POST /api/remove_chat/{chat_id}` currently returns:

- `success`
- `message`

`POST /api/chat_read_option/{user_id}` currently returns:

- `success`
- `message`

## Authorization Behavior Covered

- `GET /chat/own/remove/{id}` only deletes a message when the authenticated
  web user is the sender or receiver.
- `POST /my_message_react` only updates a reaction when the authenticated web
  user is the sender or receiver.
- `GET /api/chat_msg/{message_thread}` only returns messages when the Sanctum
  user participates in the requested thread.
- `POST /api/remove_chat/{chat_id}` only deletes a message when the Sanctum
  user is the sender or receiver.
- `POST /api/react_chat` only updates a reaction when the Sanctum user is the
  sender or receiver.
- Legacy API authorization failures keep HTTP 200 transport compatibility and
  return the standard `AUTHORIZATION_ERROR` payload.

## Known Unsafe Behavior

- Web chat deletion still uses state-changing `GET chat/own/remove/{id}`;
  participant authorization is enforced, but the route verb still needs a
  CSRF-protected migration.
- The standalone `chat.read` web route keeps its query-string `id` contract and
  now resolves that input through the Laravel `Request` object.
- `search.chat` still performs a per-contact last-message lookup in the
  controller. Move that query work into a scoped query/action with query-count
  coverage before broadening the search UI.
- Web chat upload validation has no explicit file-size limit in the current
  controller path.
- Web chat executable uploads currently reach `FileUploader`, throw a 500, and
  leave the `message_thrades`/`chats` rows created while no `media_files` row is
  created.
- API chat upload validation rejects invalid extensions before creating media
  rows, but the chat row is created before validation completes.

## Future Rename Plan

Use an expand-and-contract migration/refactor plan instead of a blind rename:

- `message_thrades` -> `message_threads`
- `message_thrade` -> `message_thread_id`
- `reciver_id` -> `receiver_id`
- `reciver` -> `receiver`
- `chatcenter` -> `chat_center`
- legacy `msg_thrade` terminology -> `message_thread`

The current checkout already uses `{message_thread}` for the API message route
parameter, but legacy mobile/API payload keys and typo storage names still need
an explicit compatibility plan before they are removed.

Recommended sequence:

1. Keep the characterization tests green.
2. Backfill production clean columns from legacy fields.
3. Monitor dual writes and add production checks for mismatched old/new values.
4. Switch reads to clean columns only after mismatch monitoring is clean.
5. Plan either a `message_thrades` -> `message_threads` table rename or a
   compatibility table/view strategy.
6. Deprecate old API parameter names and payload keys with compatibility aliases.
7. Drop legacy columns only in a later major/deployment step after production
   data and clients are verified.
