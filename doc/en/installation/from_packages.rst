==============
Using packages
==============

Merethis provides RPM for its products through Centreon Entreprise Server (CES). Open source products are available for free from our
repository.

These packages have been successfully tested with CentOS 5 and RedHat 5.

*************
Prerequisites
*************

In order to use RPM from the CES repository, you have to install the appropriate repo file. Run the following command as privileged user::

  $ wget http://yum.centreon.com/standard/ces-standard.repo -O /etc/yum.repos.d/ces-standard.repo

The repo file is now installed.

************
Installation
************

Use following documentation to install the module

.. toctree::

   rpm_server/index

