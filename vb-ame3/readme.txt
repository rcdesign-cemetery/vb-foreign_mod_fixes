Original mod by The Geek: http://www.vbulletin.org/forum/showthread.php?t=230707

Fixed bugs:
-----------

1. Tail code satisfies latest RFC xxx. AME doesn't fail with any working links.

2. Regular expression splitted, to avoid some improper gready searches.

3. "Automatically convert links into videos" didn't saved properly.
   Hook "data_postsave" splitted to correctly catch message id for each type of post.

4. Inline editor caused reset "Automatically convert links into videos" parameter
   (wrong ajax parameters catch)

5. Added Hook, no more vb patches required

Other changes:
--------------

1. Zone numbers done via "define" and moved to file start

2. Removed unused constructor from child classes

3. zone & messageid getters separated from class & polished with vB style.

Known issues:
-------------

1. "Automatically convert links into videos" markers not cleared for deleted posts.

   - can be don via cronjob. Hint: no needs to keep old markers at all, we can clear
     everything, older than 1-2 months. That simplifies SQL query.
     (will it affect "rebuild posts"  functionality ?)
     
2. Can't be disabled for vbcms articles now - no appripriate hooks.
