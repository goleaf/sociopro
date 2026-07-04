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

The PHP models currently expose canonical aliases such as `receiver_id`,
`message_thread_id`, and `chat_center`, but those aliases still write to the
legacy database columns above.

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
- `msg_thrade` -> `message_thread`

Recommended sequence:

1. Keep the characterization tests green.
2. Add additive canonical columns or compatibility views/accessors where needed.
3. Backfill canonical fields from legacy fields.
4. Update write paths to dual-write temporarily.
5. Update read paths to prefer canonical names with legacy fallback.
6. Migrate routes and public API keys with compatibility aliases where required.
7. Remove legacy names only after production data and clients are verified.
