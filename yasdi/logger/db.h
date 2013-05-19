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

#ifndef __db_h__
#define __db_h__

#include <my_global.h>
#include <mysql.h>

#include "consts.h"

void dbInit(char * iniFilename, int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char dbTypes[][MAX_COLUMN_TYPE_LEN]);

int insertValues(int channelCount, char dbColumns[][MAX_COLUMN_NAME_LEN], char channelValues[][MAX_CHANNEL_VALUE_LEN]);

void dbShutdown();

#endif

