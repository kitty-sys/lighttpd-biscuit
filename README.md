# Lighttpd and MariaDB Administration and Management Tools

## Overview

This repository provides a set of administration and management tools for Lighttpd and MariaDB. One of the core components is a PHP script designed to manage the restarting of the Lighttpd web server with minimal downtime risk. The tool automatically handles backup and revert operations, ensuring that a failed restart will not disrupt your web service significantly.

## Features

- **Safe Restarting of Lighttpd**: The tool ensures that any failed attempts to restart Lighttpd will automatically revert the web server configuration back to the last known good state, preventing outages.

- **Automatic Configuration Backup**: Before initiating a restart, the script automatically creates a backup of the current Lighttpd configuration files. The backup is stored in a designated directory and retained for easy recovery, with a limit of the last 7 backups.

- **Configuration Validation**: The script checks the Lighttpd configuration files for syntax errors before proceeding with any restart actions. This ensures that only valid configurations are applied, enhancing your server's reliability.

- **Easy to Use**: The script is designed to be executed from the command line and handles all necessary operations in a straightforward manner.

## Getting Started

### Prerequisites

- PHP installed on your server.
- Command line access to the server.
- Sufficient permissions to restart Lighttpd and modify its configuration files.
- The `lighttpd` service must be installed and accessible.

### Usage
- php restart_lighttpd.php

