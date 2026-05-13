Instructions for Google Drive upload integration

1) Create a Google Cloud project and enable the Google Drive API.
   - https://console.cloud.google.com/
   - Enable "Drive API" for your project.

2) Create a Service Account and download the JSON key.
   - IAM & Admin → Service Accounts → Create Service Account → Keys → Add Key → JSON
   - Save the downloaded JSON as `projet/config/google-service-account.json` (do NOT commit this file).

3) Permissions and visibility
   - The service account will upload files into its own Drive. To access files from a human account, share them explicitly or use domain-wide delegation (advanced).

4) Optional: specify a Drive folder id when posting to the upload endpoint (field `folderId`).

5) Usage in the app
   - From the export view pages click "Sauvegarder sur Google Drive". The client will generate a PDF and POST to `controllers/upload_to_drive.php`.

6) Troubleshooting
   - Ensure `openssl` and `curl` extensions are enabled in PHP.
   - Check PHP error logs for detailed errors.
