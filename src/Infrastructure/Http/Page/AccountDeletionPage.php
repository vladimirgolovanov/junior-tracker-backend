<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Page;

/**
 * The publicly reachable account deletion page demanded by the Google Play
 * "app account deletion" policy: it must load without the app installed, name
 * the app and the developer as the store listing does, and carry a prominent,
 * working way to request deletion.
 *
 * Rendered by hand because the project has no template engine and pulling Twig
 * in for two screens is not worth it.
 */
final readonly class AccountDeletionPage
{
    // Must match the Play Store listing. Verify before deploying.
    private const APP_NAME = 'Junior Tracker';
    private const DEVELOPER_NAME = 'Vladimir Golovanov';

    // TODO: confirm both before deploying — the page is useless if the address
    // bounces, and the retention period has to describe the real backups.
    private const SUPPORT_EMAIL = 'support@golovanov.me';
    private const BACKUP_RETENTION = '30 days';

    public function form(?string $error = null): string
    {
        $app = self::APP_NAME;
        $developer = self::DEVELOPER_NAME;
        $support = self::SUPPORT_EMAIL;
        $retention = self::BACKUP_RETENTION;
        $banner = null === $error
            ? ''
            : '<p class="error" role="alert">'.htmlspecialchars($error, \ENT_QUOTES, 'UTF-8').'</p>';

        return $this->document("Delete your {$app} account", <<<HTML
            <h1>Delete your {$app} account</h1>
            <p class="lead">
                This page lets you permanently delete your {$app} account and the
                data linked to it. {$app} is published by {$developer}.
            </p>

            {$banner}

            <form method="post" action="/delete-account">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" autocomplete="email" required>

                <label for="password">Password</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>

                <label class="check">
                    <input name="confirm" type="checkbox" value="1" required>
                    <span>I understand this is permanent and cannot be undone.</span>
                </label>

                <button type="submit">Delete my account</button>
            </form>

            <h2>You can also delete it in the app</h2>
            <p>
                Open {$app}, go to <strong>Settings &rarr; Delete account</strong> and
                confirm with your password. You do not need to reinstall the app.
            </p>

            <h2>What gets deleted</h2>
            <ul>
                <li>Your account: email address and password.</li>
                <li>All your sessions, so you are signed out everywhere.</li>
                <li>Any API keys you created.</li>
                <li>Invitations you sent or accepted.</li>
            </ul>
            <p>
                If you are the owner of a child profile, the profile itself is deleted
                too, together with every event, custom event type, sleep prediction and
                daily analytics record belonging to it. <strong>Anyone you invited to
                that profile loses access to this data as well</strong>, though their own
                accounts stay untouched. If you joined someone else's profile by
                invitation, only your account is removed and their data is kept.
            </p>

            <h2>What is kept, and for how long</h2>
            <p>
                Nothing is retained in the live database: the deletion is immediate and
                permanent. Encrypted database backups may still contain your data for up
                to {$retention}, after which they are rotated out and the data is gone.
                We keep no copy for analytics, advertising or profiling.
            </p>

            <h2>Need help?</h2>
            <p>
                If you cannot sign in or the form does not work for you, email
                <a href="mailto:{$support}">{$support}</a> from the address on your
                account and we will delete it for you.
            </p>
            HTML);
    }

    public function deleted(): string
    {
        $app = self::APP_NAME;
        $support = self::SUPPORT_EMAIL;
        $retention = self::BACKUP_RETENTION;

        return $this->document("Account deleted &mdash; {$app}", <<<HTML
            <h1>Your account has been deleted</h1>
            <p class="lead">
                Your {$app} account and the data linked to it have been permanently
                removed. You have been signed out on every device.
            </p>
            <p>
                Encrypted database backups may still hold a copy for up to {$retention}
                before they are rotated out. Nothing else remains.
            </p>
            <p>
                Questions? Write to <a href="mailto:{$support}">{$support}</a>.
            </p>
            HTML);
    }

    private function document(string $title, string $body): string
    {
        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title>{$title}</title>
                <style>{$this->styles()}</style>
            </head>
            <body>
                <main>{$body}</main>
            </body>
            </html>
            HTML;
    }

    private function styles(): string
    {
        return <<<'CSS'
            :root {
                color-scheme: light dark;
                --bg: #ffffff;
                --fg: #1a1a1a;
                --muted: #5a5a5a;
                --line: #d8d8d8;
                --accent: #b3261e;
                --accent-fg: #ffffff;
            }
            @media (prefers-color-scheme: dark) {
                :root {
                    --bg: #16181c;
                    --fg: #e8e8e8;
                    --muted: #a0a0a0;
                    --line: #33363c;
                    --accent: #e0554b;
                    --accent-fg: #16181c;
                }
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                padding: 2rem 1rem 4rem;
                background: var(--bg);
                color: var(--fg);
                font: 16px/1.6 system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            }
            main { max-width: 42rem; margin: 0 auto; }
            h1 { font-size: 1.6rem; line-height: 1.3; margin: 0 0 .75rem; }
            h2 { font-size: 1.1rem; margin: 2.25rem 0 .5rem; }
            .lead { color: var(--muted); margin-top: 0; }
            ul { padding-left: 1.25rem; }
            li { margin: .25rem 0; }
            a { color: inherit; }
            form {
                display: grid;
                gap: .4rem;
                margin: 1.75rem 0;
                padding: 1.25rem;
                border: 1px solid var(--line);
                border-radius: .6rem;
            }
            label { font-weight: 600; font-size: .9rem; }
            input[type=email], input[type=password] {
                width: 100%;
                padding: .6rem .7rem;
                margin-bottom: .6rem;
                font: inherit;
                color: var(--fg);
                background: var(--bg);
                border: 1px solid var(--line);
                border-radius: .4rem;
            }
            .check {
                display: flex;
                gap: .55rem;
                align-items: flex-start;
                margin: .3rem 0 1rem;
                font-weight: 400;
            }
            .check input { margin-top: .3rem; }
            button {
                padding: .7rem 1rem;
                font: inherit;
                font-weight: 600;
                color: var(--accent-fg);
                background: var(--accent);
                border: 0;
                border-radius: .4rem;
                cursor: pointer;
            }
            .error {
                margin: 0 0 1rem;
                padding: .65rem .8rem;
                color: var(--accent);
                border: 1px solid var(--accent);
                border-radius: .4rem;
                font-size: .95rem;
            }
            CSS;
    }
}
