{*
  Things to think about when a foreach test fails:
  - Block process ID might differ for some reason.
*}

{* Test foreach with all attributes applied. Also test access to the foreach
   system variable and the variable alias $myItem. *}
{foreach source=$persons id='f1' key='myKey' item='myItem' enable='iteration'}
  {$cte.foreach.f1.iteration}
  {$myKey}
  {$myItem}
{foreachelse}
{/foreach}

{* Test foreach with one set of necessary attributes, expecting 
   shorter output since the foreach system variable doesn't need
   to have additional data set. *}
{foreach source=$persons item='myItem'}
  {$myItem}
{/foreach}

{* Test foreach with another set of necessary attributes (differing from the 
   above). We're expecting it to work and the foreach system variable to be
   set. *}
{foreach id='myForeach' source=$persons}
  {$cte.foreach.myForeach.value}
{/foreach}