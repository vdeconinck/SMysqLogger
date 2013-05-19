/*  Copyright (C) 2011 Vincent Deconinck (known on google mail as user vdeconinck)

    This file is part of the SMySqLogger project.
	
    SMySqLogger is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
	
    SMySqLogger is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with SMySqLogger.  If not, see <http://www.gnu.org/licenses/>.
*/

#include <my_global.h>
#include <mysql.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "db.h"

#include "util.h"

char _iniFilename[PATH_MAX];
int _channelCount;
MYSQL * _conn;


void dbInit(char * iniFilename, int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char dbTypes[][MAX_COLUMN_TYPE_LEN])
{
	char server[30];
	char database[30];
	char rootUsername[30];
	char rootPassword[30];
	char username[30];
	char password[30];
	char query[2000];
	int errno;
	int channelNr;
    

    strcpy(_iniFilename, iniFilename);
    _channelCount = channelCount;

	GetPrivateProfileString_("DB", "root_username", "root", rootUsername, sizeof(rootUsername), _iniFilename);
	GetPrivateProfileString_("DB", "root_password", "", rootPassword, sizeof(rootPassword), _iniFilename);
	GetPrivateProfileString_("DB", "username", "sma", username, sizeof(username), _iniFilename);
	GetPrivateProfileString_("DB", "password", "smysqlogger", password, sizeof(password), _iniFilename);
	GetPrivateProfileString_("DB", "server", "localhost", server, sizeof(server), _iniFilename);
	GetPrivateProfileString_("DB", "database", "sma", database, sizeof(database), _iniFilename);

	printLog(LEVEL_DETAIL, "Connecting to MySQL as %s@%s:%s (client version: %s)\n", trim(username), trim(server), trim(database), mysql_get_client_info());

	// Init connection structure
	_conn = mysql_init(NULL);  
	if (_conn == NULL) {
		printLog(LEVEL_FATAL, "Error %u initializing connection structure : %s\n", mysql_errno(_conn), mysql_error(_conn));
		exit(-10);
	}

	// Connect to DB
	if (mysql_real_connect(_conn, server, username, password, database, 0, NULL, 0) == NULL) {
		errno = mysql_errno(_conn);
		if (errno != 1049 && errno != 1045) {
			printLog(LEVEL_FATAL, "Error %u connecting to Database: %s\n", errno, mysql_error(_conn));
			exit(-11);
		}
		else {
			// DB instance doesn't exist. Let's create it.
            // Information is sent to stdout and not logged to file for this one-shot code
			printf("Database '%s' doesn't exist. Trying to create it...", database);

			// Connect without specifying DB
			if (mysql_real_connect(_conn, server, rootUsername, rootPassword, NULL, 0, NULL, 0) == NULL) {
				printf("Error %u connecting to Database: %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-12);
			}

			// Create DB if it doesn't exist
			sprintf(query, "create database if not exists %s", database);
			if (mysql_query(_conn, query)) {
				printf("Error %u creating Database: %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-13);
			}

			// Create User if it doesn't exist
			sprintf(query, "create user %s@localhost identified by '%s'", username, password);
			if (mysql_query(_conn, query)) {
				printf("Error %u creating user: %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-14);
			}

			// Grant User rights
			sprintf(query, "GRANT ALL PRIVILEGES ON %s.* TO %s@localhost;", database, username);
			if (mysql_query(_conn, query)) {
				printf("Error %u granting rights: %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-15);
			}

			// Succeeded. Let's reconnect to new DB
			mysql_close(_conn);

			printf("Done. Reconnecting to newly created database...\n", database);
			_conn = mysql_init(NULL);  
			if (_conn == NULL) {
				printf("Error %u re-initializing connection structure : %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-16);
			}

			if (mysql_real_connect(_conn, server, username, password, database, 0, NULL, 0) == NULL) {
				printLog(LEVEL_FATAL, "Error %u connecting to new Database: %s\n", mysql_errno(_conn), mysql_error(_conn));
				exit(-17);
			}
			
			printf("\nCongratulations. A new database called '%s' has been created, with the table 'logged_values'.\n\
A new user '%s' has also been created and was granted access to the new DB.\n\
It is now strongly recommended to edit yasdi.ini to remove the\n\
root_username/root_password information.\n\nWaiting 20 seconds...\n", database, username);
			fflush(stdout);
			sleep(20);
		}
	}

	// Prepare query
	sprintf(query, "CREATE TABLE IF NOT EXISTS logged_values (logdate DATE, logtime TIME");
	for (channelNr = 0; channelNr < channelCount; channelNr++) {
		strcat(query, ", ");
		strcat(query, dbColumns[channelNr]);
		strcat(query, " ");
		strcat(query, dbTypes[channelNr]);
	}
	strcat(query, ")");

	printLog(LEVEL_DEBUG, "Executing : %s\n", query);

	if (mysql_query(_conn, query)) {
		printLog(LEVEL_FATAL, "Error %u creating table: %s\n", mysql_errno(_conn), mysql_error(_conn));
		exit(-18);
	}

	printLog(LEVEL_DETAIL, "Connection OK.\n");
}

void reconnect()
{
	char server[30];
	char database[30];
	char username[30];
	char password[30];
	int errno;
	int channelNr;
    
    mysql_close(_conn);
    
	GetPrivateProfileString_("DB", "username", "sma", username, sizeof(username), _iniFilename);
	GetPrivateProfileString_("DB", "password", "smysqlogger", password, sizeof(password), _iniFilename);
	GetPrivateProfileString_("DB", "server", "localhost", server, sizeof(server), _iniFilename);
	GetPrivateProfileString_("DB", "database", "sma", database, sizeof(database), _iniFilename);

	printLog(LEVEL_DETAIL, "Reconnecting to MySQL as %s@%s:%s (client version: %s)\n", trim(username), trim(server), trim(database), mysql_get_client_info());

	// Init connection structure
	_conn = mysql_init(NULL);  
	if (_conn == NULL) {
		printLog(LEVEL_FATAL, "Error %u reinitializing connection structure : %s\n", mysql_errno(_conn), mysql_error(_conn));
		exit(-10);
	}

	// Connect to DB
	if (mysql_real_connect(_conn, server, username, password, database, 0, NULL, 0) == NULL) {
		errno = mysql_errno(_conn);
        printLog(LEVEL_FATAL, "Error %u reconnecting to Database: %s\n", errno, mysql_error(_conn));
		exit(-11);
	}

	printLog(LEVEL_INFO, "Reconnected\n");
}


int insertValues(int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char channelValues[][MAX_CHANNEL_VALUE_LEN]) {
	char query[2000];
	int errno;
	int channelNr;
  
	// Prepare timestamp
	time_t now;
	struct tm *tm_now;
	char logdate[11];
	char logtime[9];

	now = time(NULL);
	tm_now = localtime(&now);
	strftime(logdate, sizeof(logdate), "%Y-%m-%d", tm_now);    
	strftime(logtime, sizeof(logtime), "%H:%M:%S", tm_now);    

	// TODO cache values (but no realtime refresh in web page then...). Use a MySQL write cache ?
	
	// Prepare query
	sprintf(query, "INSERT INTO logged_values (logdate, logtime");
	for (channelNr = 0; channelNr < channelCount; channelNr++) {
		strcat(query, ", ");
		strcat(query, dbColumns[channelNr]);
	}
	strcat(query, ") VALUES ('");
	strcat(query, logdate);
	strcat(query, "', '");
	strcat(query, logtime);
	strcat(query, "'");
	for (channelNr = 0; channelNr < channelCount; channelNr++) {
		strcat(query, ", '");
		strcat(query, channelValues[channelNr]);
		strcat(query, "'");
	}
	strcat(query, ")");
	
	printLog(LEVEL_DEBUG, "Executing : %s\n", query);

	// Insert values
    errno = -1;
    while (errno != 0) {
        errno = mysql_query(_conn, query);
	
        if (errno != 0) {
            printLog(LEVEL_WARNING, "Error %u performing insert into Database: %s.\n", mysql_errno(_conn), mysql_error(_conn));
            reconnect();
            sleep(1);
        }
    }
  
	return errno;
}


void dbShutdown()
{
	mysql_close(_conn);
}
