# Google OAuth setup

## Google Cloud

1. Open Google Cloud Console and create or select a project.
2. In **Google Auth Platform**, complete **Branding**, **Audience**, and **Data Access**. The application only needs the basic OpenID scopes: `openid`, `email`, and `profile`.
3. While the application is in testing mode, add every Google account that may sign in under **Audience → Test users**.
4. Open **Clients**, create an **OAuth client ID**, and choose **Web application**.
5. Add this exact authorized redirect URI for local development:

   `http://127.0.0.1:8000/auth/google/callback`

   The scheme, host, port, path, case, and trailing slash must match the Laravel URL exactly.
6. Copy the generated Client ID and Client Secret. Never put the secret in Blade, JavaScript, Git, or browser-visible environment variables.

## Laravel `.env`

```dotenv
APP_URL=http://127.0.0.1:8000
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback
```

Then run:

```bash
php artisan optimize:clear
php artisan migrate
```

Open `http://127.0.0.1:8000/login` and select **Continue with Google**.

## Stored user data

On the first successful callback the application creates a `customer` row in `users` with `username`, `fullname`, normalized `email`, a random unusable local password, `verified = true`, `google_id`, and `avatar_url`. On later logins it finds the customer by `google_id` or email and refreshes the Google identity fields. An email belonging to an `admin` or `receptionist` account is rejected, and one Google identity cannot be linked to two customer rows.

For production, use the public HTTPS callback URI in both Google Cloud and `.env`, then publish the consent screen when the application is ready.
