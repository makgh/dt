DT test remarks

For me, the code shared is terrible due to the following major reasons:

1. All Request files are missing.
2. Request all data is being used instead of validated request data.
3. Single mail function should be in helpers
4. Email/PN/SMS related functions should be in helpers.
5. Should maintain a constants file. Hard-coded constants are being used.
6. No proper logging with channels.
7. missing try/catch blog almost everywhere.
8. Database Transactions not implemented.
9. No use of Localization. Direct hard-coded messages everywhere.
10. Email/PN/SMS related subjects and content, error messages should be from localization files.
11. Commented code are still there.

I hardly put an hour or two to make it a bit clean. But BookingRepository.php file has too much in it. I clean up few functions from top of it.