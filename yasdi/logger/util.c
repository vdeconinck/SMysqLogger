/*  Copyright (C) 2011-2012 Vincent Deconinck (known on google mail as user vdeconinck)

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

#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stdarg.h>

#include "util.h"
#include "time.h"

/* logging file and level */
FILE * _logfile;
int _loggingLevel;


/**************************************************************************
initLog
**************************************************************************/
int initLog(int loggingLevel, FILE * logfile) {
    _loggingLevel = loggingLevel;
    _logfile = logfile;
}

/**************************************************************************
printLog
**************************************************************************/
int printLog(int level, const char *format, ...) {
    if (level >= _loggingLevel) {
        char buf[30];
        time_t now = time(NULL);
        struct tm *ts = localtime(&now);
        strftime(buf, sizeof(buf), "%Y-%m-%d %H:%M:%S", ts);
        fprintf(_logfile, "%s - ", buf);
        va_list args;
        va_start(args, format);
        vfprintf(_logfile, format, args);
        va_end(args);
        fflush(_logfile);
    }
}

/**************************************************************************
lightLog - doesn't add date before given string. Just adds result to the log
**************************************************************************/
int lightLog(int level, const char *format, ...) {
    if (level >= _loggingLevel) {
        va_list args;
        va_start(args, format);
        vfprintf(_logfile, format, args);
        va_end(args);
        fflush(_logfile);
    }
}

/*
 * Removes blanks (spaces, tabs, CR, LF) at the end of the given string
 * The string is modified and returned
 */
char* trimEnd (char* str) {
	while((str[0] != '\0') && ((str[strlen(str)-1] == ' ') || (str[strlen(str)-1] == '\t') || (str[strlen(str)-1] == '\r') || (str[strlen(str)-1] == '\n'))) str[strlen(str)-1]='\0';
	return str;
}

/*
 * Removes blanks (spaces, tabs, CR, LF) at the start of the given string
 * The string is modified and returned
 */
char* trimStart (char* str) {
	while((str[0] == ' ') || (str[0] == '\t') || (str[0] == '\r') || (str[0] == '\n')) {
		int i;
		for (i = 0; i < strlen(str); i++) {
			str[i]=str[i+1];
		}
	}
	return str;
}

/*
 * Removes blanks (spaces, tabs, CR, LF) at the both ends of the given string
 * The string is modified and returned
 */
char* trim(char* str) {
	return trimEnd(trimStart(str));
}

