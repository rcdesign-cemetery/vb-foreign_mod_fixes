Original mod by Valter http://www.vbulletin.org/forum/showthread.php?t=236069

Changed template "vsa_frules":
a. Removed condition, that disabled button and changed it's name to the number of seconds set
b. Moved setting value and disabled state for submit button to javascript. 

==================================================================================================================================================================

In template "vsa_frules"

FIND:

<vb:if condition="($vboptions[vsafrules_time]==0)">value="{vb:rawphrase submit}"<vb:else />value="{vb:raw vboptions.vsafrules_time}" disabled="disabled"</vb:if> /> 

REPLACE:

<vb:if condition="($vboptions[vsafrules_time]==0)">value="{vb:rawphrase submit}"<vb:else />value="{vb:raw vboptions.vsafrules_time}" disabled="disabled"</vb:if> />

TO:

value="{vb:rawphrase submit}"


FIND:

function VSaFR_buttonCounter() {
vsaafr_counter = fetch_object('vsaafr_counter'); 

REPLACE:

function VSaFR_buttonCounter() {
vsaafr_counter = fetch_object('vsaafr_counter'); 

TO: 

vsaafr_counter = fetch_object('vsaafr_counter');
vsaafr_counter.disabled = true;
vsaafr_counter.value = '{vb:raw vboptions.vsafrules_time}';
function VSaFR_buttonCounter() {

