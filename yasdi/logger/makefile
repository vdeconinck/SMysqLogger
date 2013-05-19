# Copyright (C) 2011-2012 Vincent Deconinck (known on google mail as user vdeconinck)
# 
# This file is part of the SMySqLogger project.
#	
# SMySqLogger is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#	
# SMySqLogger is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with SMySqLogger.  If not, see <http://www.gnu.org/licenses/>.


# This builds the old non-daemon version of the logger

	
# --- macros
CC=gcc
CFLAGS= -O3 -I../include/ -I../smalib -I../libs -I../projects/generic-cmake/incprj -I../os -I../core -I../protocol -Imysql_incl -DBIG_JOINS=1 -fno-strict-aliasing -DUNIV_LINUX -g 
LIBS= -Wl,-Bsymbolic-functions -rdynamic -lmysqlclient -lyasdimaster
SOURCES=db.c util.c logger.c
OBJECTS=$(SOURCES:.c=.o)
EXECUTABLE=logger


# --- targets
all:    $(SOURCES) $(EXECUTABLE)

$(EXECUTABLE): $(OBJECTS) 
	$(CC) -o $@ $(OBJECTS) $(LIBS)
        
.c.o:
	$(CC) $(CFLAGS) $< -c -o $@

# --- remove binary and executable files
clean:
	rm -f $(EXECUTABLE) $(OBJECTS)

