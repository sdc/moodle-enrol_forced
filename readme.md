# 'Forced' enrolment plugin

An enrolment plugin to force enrolment onto a specific course.

Enrols the currently logging-in user as a student onto the courses with IDs specified in the configuration, but only if they do not already have a role on that course: a user will not be enrolled (as a student) if they already have e.g. a Teacher role.
