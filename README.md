# StravaCsvUploader for RunningAhead style CSV file

Upload a CSV containing activities to your Strava account that are in same format as specified [here](https://www.runningahead.com/help/custom_csv). I wanted to keep adding via csv to both RunningAhead and Strava. RunningAhead's tab separated export file could also be sent to Strava with some modifications. I use this with in my local virtualbox ubuntu, not on my site.

## Set up

Run `composer install` in the root folder. This will bring the dependencies and autoloader in. 

Create a .cred file with your data (copy from .cred.example). Obtain those credentials from here: https://www.strava.com/settings/api


## Example (index.php)

First, you will need to authorize the app once set up.
Follow the instructions for the file format, attach it and click on submit.

Find a sample file in the **upload** folder.
