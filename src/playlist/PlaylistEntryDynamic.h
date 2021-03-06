/*
 *   PlaylistEntryDynamic Class for Falcon Player (FPP)
 *
 *   Copyright (C) 2013-2018 the Falcon Player Developers
 *      Initial development by:
 *      - David Pitts (dpitts)
 *      - Tony Mace (MyKroFt)
 *      - Mathew Mrosko (Materdaddy)
 *      - Chris Pinkham (CaptainMurdoch)
 *      For additional credits and developers, see credits.php.
 *
 *   The Falcon Player (FPP) is free software; you can redistribute it
 *   and/or modify it under the terms of the GNU General Public License
 *   as published by the Free Software Foundation; either version 2 of
 *   the License, or (at your option) any later version.
 *
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU General Public License for more details.
 *
 *   You should have received a copy of the GNU General Public License
 *   along with this program; if not, see <http://www.gnu.org/licenses/>.
 */

#ifndef _PLAYLISTENTRYDYNAMIC_H
#define _PLAYLISTENTRYDYNAMIC_H

#include <string>

#include <curl/curl.h>
#include <jsoncpp/json/json.h>

#include "PlaylistEntryBase.h"

class PlaylistEntryDynamic : public PlaylistEntryBase {
  public:
	PlaylistEntryDynamic(PlaylistEntryBase *parent = NULL);
	~PlaylistEntryDynamic();

	int  Init(Json::Value &config);

	int  StartPlaying(void);
	int  Process(void);
	int  Stop(void);

	void Dump(void);

	Json::Value GetConfig(void);

  private:
	int ReadFromCommand(void);
	int ReadFromFile(void);
	int ReadFromPlugin(void);
	int ReadFromURL(std::string url);
	int ReadFromString(std::string jsonStr);

	int ProcessData(void *buffer, size_t size, size_t nmemb);

	static size_t write_data(void *ptr, size_t size, size_t nmemb,
	                             void *ourpointer);

	std::string            m_subType;
	std::string            m_data;
	PlaylistEntryBase     *m_playlistEntry;

	std::string            m_url;
	std::string            m_method;
	std::string            m_response;
};

#endif
