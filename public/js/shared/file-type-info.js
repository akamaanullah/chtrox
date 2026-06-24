/** File extension → label + Lucide icon (DM + channel chat). */
(function () {
    function getFileExtInfo(name, mimeType) {
        var parts = String(name || 'File').split('.');
        var extRaw = parts.length > 1 ? parts.pop() : '';
        var extSlug = extRaw.toLowerCase().replace(/[^a-z0-9]/g, '') || 'file';

        if (extSlug === 'file' && mimeType) {
            if (mimeType.indexOf('pdf') !== -1) extSlug = 'pdf';
            else if (mimeType.indexOf('spreadsheet') !== -1 || mimeType.indexOf('excel') !== -1 || mimeType === 'text/csv') extSlug = 'csv';
            else if (mimeType.indexOf('word') !== -1 || mimeType.indexOf('document') !== -1) extSlug = 'doc';
            else if (mimeType.indexOf('zip') !== -1 || mimeType.indexOf('compressed') !== -1) extSlug = 'zip';
            else if (mimeType.indexOf('image/') === 0) extSlug = 'png';
            else if (mimeType.indexOf('video/') === 0) extSlug = 'mp4';
            else if (mimeType.indexOf('audio/') === 0) extSlug = 'mp3';
            else if (mimeType.indexOf('text/') === 0) extSlug = 'txt';
            else if (mimeType.indexOf('font') !== -1) extSlug = 'ttf';
            else if (mimeType === 'application/json') extSlug = 'json';
            else if (mimeType === 'application/xml' || mimeType === 'text/xml') extSlug = 'xml';
            else if (mimeType === 'application/sql') extSlug = 'sql';
            else if (mimeType.indexOf('presentation') !== -1) extSlug = 'pptx';
        }

        if (!extRaw && extSlug !== 'file') {
            extRaw = extSlug;
        }

        var typeLabels = {
            txt: 'Text Document', rtf: 'Rich Text', md: 'Markdown', log: 'Log File',
            pdf: 'PDF Document',
            doc: 'Word Document', docx: 'Word Document', odt: 'OpenDocument Text',
            xls: 'Spreadsheet', xlsx: 'Spreadsheet', ods: 'OpenDocument Sheet', csv: 'CSV File', tsv: 'TSV File',
            ppt: 'Presentation', pptx: 'Presentation', odp: 'OpenDocument Presentation',
            pages: 'Apple Pages', numbers: 'Apple Numbers', key: 'Apple Keynote',
            epub: 'eBook', mobi: 'eBook',
            png: 'Image', jpg: 'Image', jpeg: 'Image', gif: 'GIF Image',
            bmp: 'Bitmap Image', webp: 'WebP Image', svg: 'SVG Vector',
            ico: 'Icon File', tiff: 'TIFF Image', tif: 'TIFF Image',
            heic: 'HEIC Image', heif: 'HEIF Image', avif: 'AVIF Image',
            raw: 'RAW Image', cr2: 'RAW Image', nef: 'RAW Image', arw: 'RAW Image',
            psd: 'Photoshop File', ai: 'Illustrator File', sketch: 'Sketch File',
            fig: 'Figma File', xd: 'Adobe XD File',
            mp4: 'Video', mkv: 'Video', avi: 'Video', mov: 'Video', wmv: 'Video',
            flv: 'Flash Video', webm: 'WebM Video', m4v: 'Video', ogv: 'Ogg Video',
            '3gp': '3GP Video', ts: 'TypeScript', vob: 'DVD Video',
            mpg: 'MPEG Video', mpeg: 'MPEG Video', rm: 'RealMedia Video',
            mp3: 'Audio', wav: 'WAV Audio', aac: 'AAC Audio', flac: 'FLAC Audio',
            ogg: 'Ogg Audio', m4a: 'M4A Audio', wma: 'WMA Audio', aiff: 'AIFF Audio',
            opus: 'Opus Audio', mid: 'MIDI File', midi: 'MIDI File', amr: 'AMR Audio',
            zip: 'ZIP Archive', rar: 'RAR Archive', tar: 'TAR Archive',
            gz: 'GZip Archive', '7z': '7-Zip Archive', bz2: 'BZip2 Archive',
            xz: 'XZ Archive', iso: 'Disk Image', dmg: 'Mac Disk Image',
            cab: 'Cabinet Archive', tgz: 'Tar GZip Archive',
            js: 'JavaScript', mjs: 'ES Module', cjs: 'CommonJS Module',
            jsx: 'React JSX', tsx: 'React TSX',
            html: 'HTML File', htm: 'HTML File', xhtml: 'XHTML File',
            css: 'Stylesheet', scss: 'SCSS Stylesheet', sass: 'Sass Stylesheet', less: 'Less Stylesheet',
            php: 'PHP File', py: 'Python File', rb: 'Ruby File', java: 'Java File',
            c: 'C Source', cpp: 'C++ Source', h: 'C Header', cs: 'C# File',
            go: 'Go File', rs: 'Rust File', swift: 'Swift File', kt: 'Kotlin File',
            dart: 'Dart File', r: 'R Script', m: 'MATLAB / Obj-C File',
            sh: 'Shell Script', bash: 'Bash Script', zsh: 'Zsh Script',
            bat: 'Batch File', cmd: 'Command File', ps1: 'PowerShell Script',
            lua: 'Lua Script', pl: 'Perl Script', sql: 'SQL File',
            graphql: 'GraphQL Schema', gql: 'GraphQL Schema',
            yaml: 'YAML File', yml: 'YAML File',
            json: 'JSON File', json5: 'JSON5 File', jsonl: 'JSONL File',
            xml: 'XML File', toml: 'TOML File', ini: 'INI Config', cfg: 'Config File', env: 'Env File',
            dockerfile: 'Dockerfile', makefile: 'Makefile',
            vue: 'Vue Component', svelte: 'Svelte Component',
            wasm: 'WebAssembly', asm: 'Assembly File',
            ex: 'Elixir File', exs: 'Elixir Script',
            erl: 'Erlang File', hs: 'Haskell File', ml: 'OCaml File',
            scala: 'Scala File', clj: 'Clojure File', groovy: 'Groovy File',
            db: 'Database File', sqlite: 'SQLite Database', sqlite3: 'SQLite Database',
            mdb: 'Access Database', accdb: 'Access Database',
            parquet: 'Parquet File', avro: 'Avro Data File',
            ttf: 'TrueType Font', otf: 'OpenType Font', woff: 'Web Font', woff2: 'Web Font',
            eot: 'Embedded Font',
            exe: 'Windows Executable', msi: 'Windows Installer',
            apk: 'Android Package', ipa: 'iOS Package',
            deb: 'Debian Package', rpm: 'RPM Package',
            app: 'Mac Application', bin: 'Binary File', run: 'Linux Executable',
            dll: 'DLL Library', so: 'Shared Library', dylib: 'Mac Library',
            sys: 'System File', drv: 'Driver File',
            torrent: 'Torrent File', ics: 'Calendar File', vcf: 'Contact File',
            kml: 'KML Geo File', gpx: 'GPS Data', geojson: 'GeoJSON File',
            msg: 'Outlook Message', eml: 'Email File',
            bak: 'Backup File', tmp: 'Temp File', lock: 'Lock File',
            cert: 'Certificate', crt: 'Certificate', pem: 'PEM File',
            p12: 'PKCS#12 File', pfx: 'PFX Certificate'
        };

        var iconMap = {
            txt: 'file-text', rtf: 'file-text', md: 'file-text', log: 'file-text',
            pdf: 'file-text',
            doc: 'file-text', docx: 'file-text', odt: 'file-text',
            epub: 'book-open', mobi: 'book-open',
            pages: 'file-text',
            xls: 'file-spreadsheet', xlsx: 'file-spreadsheet', ods: 'file-spreadsheet',
            csv: 'file-spreadsheet', tsv: 'file-spreadsheet', numbers: 'file-spreadsheet',
            ppt: 'presentation', pptx: 'presentation', odp: 'presentation', key: 'presentation',
            png: 'image', jpg: 'image', jpeg: 'image', gif: 'image', bmp: 'image',
            webp: 'image', ico: 'image', tiff: 'image', tif: 'image',
            heic: 'image', heif: 'image', avif: 'image',
            raw: 'image', cr2: 'image', nef: 'image', arw: 'image',
            svg: 'file-code',
            psd: 'layers', ai: 'pen-tool', sketch: 'pen-tool', fig: 'pen-tool', xd: 'pen-tool',
            mp4: 'video', mkv: 'video', avi: 'video', mov: 'video', wmv: 'video',
            flv: 'video', webm: 'video', m4v: 'video', ogv: 'video',
            '3gp': 'video', vob: 'video', mpg: 'video', mpeg: 'video', rm: 'video',
            mp3: 'music', wav: 'music', aac: 'music', flac: 'music', ogg: 'music',
            m4a: 'music', wma: 'music', aiff: 'music', opus: 'music', amr: 'music',
            mid: 'music-2', midi: 'music-2',
            zip: 'archive', rar: 'archive', tar: 'archive', gz: 'archive',
            '7z': 'archive', bz2: 'archive', xz: 'archive', tgz: 'archive', cab: 'archive',
            iso: 'disc', dmg: 'disc',
            js: 'file-code', mjs: 'file-code', cjs: 'file-code',
            ts: 'file-code', jsx: 'file-code', tsx: 'file-code',
            html: 'file-code', htm: 'file-code', xhtml: 'file-code',
            css: 'file-code', scss: 'file-code', sass: 'file-code', less: 'file-code',
            php: 'file-code', py: 'file-code', rb: 'file-code', java: 'file-code',
            c: 'file-code', cpp: 'file-code', h: 'file-code', cs: 'file-code',
            go: 'file-code', rs: 'file-code', swift: 'file-code', kt: 'file-code',
            dart: 'file-code', r: 'file-code', m: 'file-code',
            sh: 'terminal', bash: 'terminal', zsh: 'terminal',
            bat: 'terminal', cmd: 'terminal', ps1: 'terminal',
            lua: 'file-code', pl: 'file-code', wasm: 'cpu', asm: 'cpu',
            ex: 'file-code', exs: 'file-code', erl: 'file-code', hs: 'file-code',
            ml: 'file-code', scala: 'file-code', clj: 'file-code', groovy: 'file-code',
            vue: 'file-code', svelte: 'file-code',
            dockerfile: 'container', makefile: 'settings-2',
            json: 'file-braces', json5: 'file-braces', jsonl: 'file-braces',
            yaml: 'file-braces', yml: 'file-braces',
            xml: 'file-code', toml: 'file-braces', ini: 'settings', cfg: 'settings', env: 'settings',
            sql: 'database',
            graphql: 'share-2', gql: 'share-2',
            db: 'database', sqlite: 'database', sqlite3: 'database',
            mdb: 'database', accdb: 'database',
            parquet: 'database', avro: 'database',
            ttf: 'type', otf: 'type', woff: 'type', woff2: 'type', eot: 'type',
            exe: 'package', msi: 'package',
            apk: 'smartphone', ipa: 'smartphone',
            deb: 'package', rpm: 'package',
            app: 'package', bin: 'binary', run: 'package',
            dll: 'library', so: 'library', dylib: 'library',
            sys: 'cpu', drv: 'cpu',
            torrent: 'download', ics: 'calendar', vcf: 'contact',
            kml: 'map-pin', gpx: 'map-pin', geojson: 'map',
            msg: 'mail', eml: 'mail',
            bak: 'clock', tmp: 'clock', lock: 'lock',
            cert: 'shield', crt: 'shield', pem: 'shield',
            p12: 'shield', pfx: 'shield'
        };

        var iconCategory = 'default';
        if (iconMap[extSlug]) {
            var icon = iconMap[extSlug];
            if (icon === 'file-spreadsheet' || extSlug === 'csv' || extSlug === 'tsv' || extSlug === 'numbers') iconCategory = 'spreadsheet';
            else if (icon === 'presentation') iconCategory = 'presentation';
            else if (icon === 'image' || icon === 'layers' || icon === 'pen-tool') iconCategory = 'image';
            else if (icon === 'video') iconCategory = 'video';
            else if (icon === 'music' || icon === 'music-2') iconCategory = 'audio';
            else if (icon === 'archive' || icon === 'disc') iconCategory = 'archive';
            else if (icon === 'file-code' || icon === 'terminal' || icon === 'container' || icon === 'cpu') iconCategory = 'code';
            else if (icon === 'database' || icon === 'file-braces' || icon === 'settings' || icon === 'settings-2' || icon === 'share-2') iconCategory = 'data';
            else if (icon === 'type') iconCategory = 'font';
            else if (icon === 'package' || icon === 'smartphone' || icon === 'library' || icon === 'binary') iconCategory = 'package';
            else if (extSlug === 'pdf') iconCategory = 'pdf';
            else if (icon === 'file-text' || icon === 'book-open') iconCategory = 'document';
            else iconCategory = 'default';
        }

        return {
            extLabel: (extRaw || extSlug).toUpperCase(),
            extSlug: extSlug,
            typeLabel: typeLabels[extSlug] || 'File',
            lucideIcon: iconMap[extSlug] || 'file',
            iconCategory: iconCategory
        };
    }

    window.ChatRoxFileType = {
        getFileExtInfo: getFileExtInfo
    };
})();
