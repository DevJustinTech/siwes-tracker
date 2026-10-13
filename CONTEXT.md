# SIWES Tracker

A student's record of daily work and progress during industrial training.

## Language

**Student**:
The person who owns a log book and records their industrial-training activity.

**Daily entry**:
One student's record for one date worked, including hours, work done, and optional challenges and remarks. A student may have at most one entry for a date.
_Avoid_: Weekly entry

**First logged day**:
The earliest date worked in a student's current log book, not the date the record was created. Adding an earlier entry or deleting the earliest one changes this anchor.

**SIWES week**:
A numbered seven-day window starting at the first logged day; the first window is Week 1. These are not Monday-based calendar weeks.
_Avoid_: Calendar week
_Implemented by_: `weeks.php` (`siwes_week_number()`), covered by `tests/weeks.php`

**Week span**:
The SIWES week number of the last logged day; an empty log book has a span of zero. Gaps without entries still count toward the span.
_Implemented by_: `weeks.php` (`siwes_week_span()`)

**Logged day**:
A date for which the student has a daily entry. The hours chart shows up to the latest ten logged days, not ten consecutive calendar days.
