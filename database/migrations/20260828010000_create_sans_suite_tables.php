<?php
declare(strict_types=1);

return new class {
    public function up(\PDO $pdo): void
    {
        $pdo->exec('PRAGMA foreign_keys = ON');

        $pdo->exec(<<<'SQL'
CREATE TABLE applications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    kind TEXT NOT NULL CHECK (kind IN ('listing', 'spontaneous')),
    company TEXT NOT NULL,
    location TEXT NOT NULL DEFAULT '',
    position TEXT NOT NULL,
    applied_at TEXT NOT NULL,
    source_site TEXT NOT NULL DEFAULT '',
    listing_url TEXT NOT NULL DEFAULT '',
    job_description TEXT NOT NULL DEFAULT '',
    cover_letter TEXT NOT NULL DEFAULT '',
    notes TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'waiting' CHECK (status IN ('waiting', 'contact', 'interview', 'offer', 'accepted', 'rejected', 'no_response')),
    interview_preparation TEXT NOT NULL DEFAULT '',
    interview_questions TEXT NOT NULL DEFAULT '',
    interview_debrief TEXT NOT NULL DEFAULT '',
    archived_at TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE activities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    type TEXT NOT NULL CHECK (type IN ('application', 'follow_up', 'response', 'call', 'interview_scheduled', 'interview_completed', 'offer', 'rejection', 'note')),
    occurred_at TEXT NOT NULL,
    due_at TEXT NULL,
    note TEXT NOT NULL DEFAULT '',
    origin TEXT NOT NULL DEFAULT 'user' CHECK (origin IN ('user', 'system')),
    completed_at TEXT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE contacts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT '',
    email TEXT NOT NULL DEFAULT '',
    phone TEXT NOT NULL DEFAULT '',
    linkedin_url TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec(<<<'SQL'
CREATE TABLE attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    application_id INTEGER NOT NULL,
    original_name TEXT NOT NULL,
    stored_name TEXT NOT NULL UNIQUE,
    mime_type TEXT NOT NULL,
    size INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
)
SQL);

        $pdo->exec('CREATE INDEX idx_applications_archived_at ON applications(archived_at)');
        $pdo->exec('CREATE INDEX idx_applications_status ON applications(status)');
        $pdo->exec('CREATE INDEX idx_applications_applied_at ON applications(applied_at)');
        $pdo->exec('CREATE INDEX idx_applications_company ON applications(company)');
        $pdo->exec('CREATE INDEX idx_activities_application_occurred ON activities(application_id, occurred_at)');
        $pdo->exec('CREATE INDEX idx_activities_due_at ON activities(due_at) WHERE due_at IS NOT NULL');
        $pdo->exec('CREATE INDEX idx_activities_application_origin ON activities(application_id, origin)');
        $pdo->exec('CREATE INDEX idx_activities_completed_at ON activities(completed_at) WHERE completed_at IS NOT NULL');
        $pdo->exec('CREATE INDEX idx_contacts_application_id ON contacts(application_id)');
        $pdo->exec('CREATE INDEX idx_attachments_application_id ON attachments(application_id)');
        $pdo->exec('PRAGMA optimize');
    }

    public function down(\PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS attachments');
        $pdo->exec('DROP TABLE IF EXISTS contacts');
        $pdo->exec('DROP TABLE IF EXISTS activities');
        $pdo->exec('DROP TABLE IF EXISTS applications');
    }
};
