{*
  Things to think about when a section test fails:
  - Block process ID might differ for some reason.
*}

{* Simple Section (with data printout) *}
{section id='p' source=$persons}
Name: {$persons[p].name}
Age: {$persons[p].age}
{/section}

{* Section (with else statement) *}
{section id='p' source=$persons}
{sectionelse}
{/section}

{* Section with Optional Attributes (1) *}
{section id='p' source=$persons start=1}
{/section}

{* Section with Optional Attributes (2) *}
{section id='p' source=$persons start=1 max=5}
{/section}

{* Section with Optional Attributes (3) *}
{section id='p' source=$persons start=1 max=5 step=2}
{/section}

{* Section with enable-attribute *}
{section id='p' source=$persons enable='first,last,size,index,iteration'}
{/section}