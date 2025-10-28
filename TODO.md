# TODO: Add Image Sharing to Chat

## Steps to Complete

- [x] Update db.sql: Add 'image' VARCHAR(255) column to the messages table.
- [x] Modify chat.php: Change form enctype to multipart/form-data and add file input for images (accept jpg, png, gif).
- [x] Modify chat.php: Add PHP logic to handle image uploads - validate size (<=300KB), type, move to uploads/ with unique name, insert into DB (set message to 'Image' if no text).
- [x] Modify chat.php: Update message display in HTML to show <img> tag if image exists.
- [x] Update get_messages.php: Include 'image' in the SELECT query and JSON response.
- [x] Update JavaScript polling in chat.php: When displaying messages, check for image and render <img> if present.
- [x] Run the updated db.sql to alter the table in the database. (Created alter_table.php script to run the ALTER TABLE command)
- [x] Test image upload and display in chat. (User requested to increase image size by 200%, which has been implemented by changing max-width and max-height from 200px to 600px in both PHP and JavaScript rendering.)
- [x] Ensure images are deleted with old messages (24 hours) - add logic to delete image files when messages are deleted.

## Progress Tracking
- Started: [Date/Time]
- Completed: [Date/Time]
