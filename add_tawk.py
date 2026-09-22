#!/usr/bin/env python3
"""
add_tawk.py

Inserts the tawk.to chat widget script into every .html file inside a folder
(and its subfolders), right before the closing </body> tag.

HOW TO USE:
1. Paste your tawk.to widget <script> code into the TAWK_SCRIPT variable below.
2. Set SITE_FOLDER to the path of your local website folder.
3. Run:  python3 add_tawk.py
4. Upload the updated files back to Hostinger (public_html), overwriting the originals.

Safe to run more than once — it skips files that already contain the tawk.to script.
"""

import os
import re
import shutil

# Your tawk.to widget code (Property ID: 66a63f20becc2fed692c2214, Widget ID: 1i3smd6el)
EMBED_URL = "embed.tawk.to/66a63f20becc2fed692c2214/1i3smd6el"
TAWK_SCRIPT = """
<!--Start of Tawk.to Script-->
<script type="text/javascript">
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/66a63f20becc2fed692c2214/1i3smd6el';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();
</script>
<!--End of Tawk.to Script-->
"""

# ── SET THE PATH TO YOUR LOCAL WEBSITE FOLDER ───────────────────────────────
SITE_FOLDER = r"C:\Users\USER\Documents\My Project\cims"

# Tag matcher: finds the LAST </body> in the file, case-insensitively,
# regardless of stray whitespace (e.g. "</ body>" or "</BODY>").
BODY_CLOSE_RE = re.compile(r"</\s*body\s*>", re.IGNORECASE)
HTML_CLOSE_RE = re.compile(r"</\s*html\s*>", re.IGNORECASE)


def read_text(filepath):
    """Try UTF-8 first (most common), fall back to Windows-1252 if that fails,
    so we don't silently corrupt accented characters etc."""
    raw = open(filepath, "rb").read()
    for encoding in ("utf-8", "cp1252"):
        try:
            return raw.decode(encoding), encoding
        except UnicodeDecodeError:
            continue
    # Last resort: decode with replacement chars, but flag it
    return raw.decode("utf-8", errors="replace"), "utf-8 (with replacement chars — check this file manually)"


def add_widget_to_file(filepath):
    content, encoding_used = read_text(filepath)

    # Skip only if THIS exact widget embed URL is already present (avoids
    # false positives from the word "tawk.to" appearing in visible text).
    if EMBED_URL in content:
        return "skipped (widget already installed)"

    matches = list(BODY_CLOSE_RE.finditer(content))
    if matches:
        # Insert before the LAST </body> only, so files with more than one
        # occurrence (e.g. malformed HTML) don't get the widget duplicated.
        last = matches[-1]
        content = content[: last.start()] + TAWK_SCRIPT + "\n" + content[last.start() :]
    else:
        html_matches = list(HTML_CLOSE_RE.finditer(content))
        if html_matches:
            last = html_matches[-1]
            content = content[: last.start()] + TAWK_SCRIPT + "\n" + content[last.start() :]
        else:
            content += TAWK_SCRIPT

    # Back up the original file first (only once — don't overwrite an
    # existing .bak from a previous run).
    backup_path = filepath + ".bak"
    if not os.path.exists(backup_path):
        shutil.copy2(filepath, backup_path)

    with open(filepath, "w", encoding="utf-8") as f:
        f.write(content)

    note = "" if encoding_used == "utf-8" else f" (re-encoded from {encoding_used})"
    return f"updated{note}"


def main():
    if not os.path.isdir(SITE_FOLDER):
        print(f"ERROR: '{SITE_FOLDER}' is not a valid folder. Edit SITE_FOLDER at the top of this script.")
        return

    updated, skipped = 0, 0

    for root, _dirs, files in os.walk(SITE_FOLDER):
        for filename in files:
            if filename.lower().endswith(".html"):
                filepath = os.path.join(root, filename)
                result = add_widget_to_file(filepath)
                print(f"{result:45s} {filepath}")
                if result.startswith("updated"):
                    updated += 1
                else:
                    skipped += 1

    print(f"\nDone. {updated} file(s) updated, {skipped} file(s) skipped.")
    print("Original files were backed up as <filename>.html.bak next to each edited file.")


if __name__ == "__main__":
    main()
