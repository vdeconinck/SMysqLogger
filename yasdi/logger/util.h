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

#ifndef __util_h__
#define __util_h__

#define LEVEL_DEBUG 0
#define LEVEL_DETAIL 1
#define LEVEL_INFO 2
#define LEVEL_IMPORTANT 3
#define LEVEL_WARNING 4
#define LEVEL_ERROR 5
#define LEVEL_FATAL 6

/**************************************************************************
initLog
**************************************************************************/
int initLog(int loggingLevel, FILE * logfile);

/**************************************************************************
printLog
**************************************************************************/
int printLog(int level, const char *format, ...);

/**************************************************************************
lightLog - doesn't add date before given string. Just adds result to the log
**************************************************************************/
int lightLog(int level, const char *format, ...);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the end of the given string
 * The string is modified and returned
 */
char* trimEnd (char* str);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the start of the given string
 * The string is modified and returned
 */
char* trimStart (char* str);

/*
 * Removes blanks (spaces, tabs, CR, LF) at the both ends of the given string
 * The string is modified and returned
 */
char* trim (char* str);

#endif

