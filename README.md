# Ferris Jukebox

A lightweight, guest-safe Jukebox plugin for Falcon Player (FPP) 10.x, built for MP4 projection shows.

## v1
- Reads playlists already created in FPP.
- Administrator chooses which playlists guests may request.
- Mobile Pirate Cove guest page.
- Starts an approved playlist once, non-repeating.
- Displays the current playlist and locks requests while a show is active.
- MP4-only playlists are supported; FSEQ sequences are not required.
- No cloud service or external dependencies.

## Install for testing
In FPP 10.2, use the Plugin Manager manual/install-from-repository option with this repository:
https://github.com/rderbid/fpp-ferris-jukebox

After installation, open **Content Setup -> Ferris Jukebox**, select allowed playlists, and save. Then click **Open Guest Jukebox**.

The guest API validates every request against the administrator allowlist; it cannot launch arbitrary FPP commands.

Target: FPP 10.2 / Raspberry Pi 3B+.
