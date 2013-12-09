/*****************************************************************************************************
*                               Advanced Virtual Host BruteForcer (AVHBF)
*       author  : Sergey Belov (sergeybelove@gmail.com)
*       repo    : http://github.com/
*       version : 0.1 (24 November'2013)
*
*       What is it?
*               This tool try to bruteforce virtualhosts on your target server
*               (HOST HTTP header) and try to find something valid...
*
*       Why this tool is better than msf module?
*               + Have a hybrid mode. When domain name is provided this
*                 tool try to find test.domain,  dev.domain e.t.c... (taking subdomains from dictionary)
*               + Try to find .dev / .test / .local zones for current domain and for all subdomains while bruteforcing
*               + "Smart" detect mechanism. Comparing not equal or not full text between non-exist domain and testing domain and not headers
*               (nginx replies for non-exist domain 200 HTTP code). You can set in percent what is the maximum
*               difference in responses between non-exist and founded domain.
*               + Have a bigger than msf dictionary (include msf domains of course)
*               + Preventing false-positive answers
*               + Not required msf :P
*               * Also try to bruteforce with HOST header from domains.txt file (standard msf feature)
*
*       Requirements
*               + console mode
*               + PHP > 5.3
*               + cURL (+ php cURL lib)
*               * xdiff - optional lib. It will be better if you install it
*               How to install: http://serverfault.com/questions/362680/installing-xdiff-locally-with-apache-php
*
*       ToDo:
*               + Write same browser's extensions
*               + 2 fingerprints for non-exist domains (will solve google's services problem)
*
*       License: GPL v2
*
*       =============== A lot of success stories happened with this tool ... ===============
*
******************************************************************************************************/
