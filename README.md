Generic Source Reader
=====================

Use this extension to generate the definitions arrays for nl.pum.generic
Compatibel with nl.pum.generic version 1.1


Step 1:
Copy the database containing the entities that you want "hard coded" to e.g. a local a civi environment, where you intend to use this module
Make sure the database copy can be accessed using the same username/password as your operational (local) civi installation

Step 2:
In ./nl.pum.genericsourcereader/CRM/Genericsourcereader/Page/CopyTables.php make a few adjustments:
modify line 16 ($db) to refer to your copied database
modify line 17 ($baseDir) to specify a directory relative to your CRS root directory (make sure to add a trailing '/' when $baseDir is not empty)
modify line 18 to 23 to your preferences:
	$logName = the name of the file that records this extensions progress and errors
	$logExport = handle to the log file (w for overwrite) - please don't change
	$eol = "\r\n"; = new line character (\n should be sufficient in most cases, \r was added for Windows environments)
	$tab = "\t"; = character(s) used to indent the generated code. Likely a tab or a number of spaces
	$lblOk = prefix to non-error lines in the log
	$lblErr = prefix to error lines in the log

Step 3:
In ./nl.pum.genericsourcereader/CRM/Genericsourcereader/Page/CopyTables.php (same file as above) define the entities that you would like to prepare for nl.pum.generic
Make adjustments to the $required array starting in line 148

Step 4:
Open page <host>/civicrm/copytables

Step 5:
Check the log file and move the .def.inc.php files from the root of your CMS to the root of nl.pum.generic.
Be aware that leaving the files in the CMS-root will cause civi to use these, instead of any copied file in nl.pum.generic.


IMPORTANT:
This module lists all parts of the requested entities, including the ones that were created by other extensions.
If a different extention adds e.g. values to option group "activity_type", "activity_status", "case_type" or "case_status" all of their values will show in the generated lists.
You may need to review your .def.inc.php files and delete a few lines entries from them, before you feed them into nl.pum.generic!
