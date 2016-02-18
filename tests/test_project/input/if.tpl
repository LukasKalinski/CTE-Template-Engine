{* Simple comparation of two variables... *}
{if $foo.bar equals $user}
{/if}

{* Testing with PHP operators... *}
{if $foo.bar == 'foobar!' && $user == $foo.bar}
{/if}

{* Testing:
  - Parentheses
  - Boolean variables / logical not
  - Elseif + Else
*}
{if ($page is equal to 0 or $page is greater than 77) and $isNewYear}
{elseif not $isNewYear}
{else}
{/if}

{* Testing:
  - Function operators
  - Arithmetic expressions
  - Shorter operator aliases (i.e. 'gt' instead of 'is greater than')
*}
{if $page is set and ($page gt 0 or $page is div by 3) and $user not empty}
{elseif $page eq $maxPage-1}
{/if}

{* Testing:
   - Parseable Strings in three combinations
*}
{if $user equals "{$foo.bar} Freeman"}
{elseif $user equals "Freeman {$foo.bar}"}
{elseif $user equals "Mr. {$foo.bar} Freeman"}
{/if}

{* Testing:
   - Parseable Strings with inline arithmetic expression (this is not a good way
     to do it in if tags, used for test purposes only).
*}
{if $page equals "{$maxPage-2}"}
{/if}