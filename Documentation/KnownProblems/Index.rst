.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

Currently we only support copying the cropArea of the first cropVariant. So, you can't decide to copy
the cropArea of the second or fourth cropVariant bck to all other cropVariants

In our DataHandler Hook we currently don't check, if first cropArea with its ratio is allowed for all other
cropVariants. So please try to keep the ratios for all cropVariants the same! In detail: the ratio of the first
cropVariant has to be configured in all other cropVariants. It is no problem, if you have configured
further ratios for all other cropVariants.

