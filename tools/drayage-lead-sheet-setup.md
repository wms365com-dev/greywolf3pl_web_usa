# Drayage Lead Sheet Setup

This lets the drayage page save `draft` and `submitted` lead states into a shared Google Sheet.

## 1. Create the sheet

1. Create a new Google Sheet for drayage leads.
2. Name the workbook something like `Grey Wolf Drayage Leads`.
3. Share it with the Grey Wolf team members who should review lead drafts.

## 2. Add the Apps Script

1. Open the Google Sheet.
2. Go to `Extensions` -> `Apps Script`.
3. Replace the default script with the contents of:
   [tools/drayage-lead-sheet-webapp.gs](/E:/GreyWolfWebsite/tools/drayage-lead-sheet-webapp.gs)
4. Save the project.

## 3. Deploy as a web app

1. In Apps Script, click `Deploy` -> `New deployment`.
2. Choose `Web app`.
3. Description: `Grey Wolf drayage lead capture`
4. Execute as: `Me`
5. Who has access: `Anyone`
6. Deploy and copy the web app URL.

## 4. Confirm the live relay settings

The live website now posts draft updates to:

- [drayage-draft-sync.php](/E:/GreyWolfWebsite/drayage-draft-sync.php)

That PHP relay forwards the data to the Google Apps Script web app. The current Grey Wolf values already wired into this build are:

- Sheet ID: `1mlCS1PypAuSQU3ep9JlTeetg3nlRXQ76_tHQjGVGfTY`
- Web app URL: `https://script.google.com/macros/s/AKfycbzKtrMBi3_Z5thT2MIU1ACdRlJwtuQ-CXIYkDJCxB7CtLoH-owo3fixF1ddR1e877gb/exec`

## 5. What the site sends

The drayage page sends:

- `draft` when the visitor has entered enough contact info for Grey Wolf to follow up
- `submitted` when the visitor sends the full form

The Sheet stores:

- tracking ID
- lead status
- consent flag
- step reached
- timestamps
- UTM source/medium/campaign
- contact details
- load details
- ship-from and ship-to address fields
- notes

## 6. How to use it

- Filter `status = draft` to review incomplete drayage leads
- Filter `status = submitted` to see completed requests

## 7. Important privacy note

The live drayage page and privacy policy should continue to disclose that in-progress drayage requests may be saved for follow-up.
