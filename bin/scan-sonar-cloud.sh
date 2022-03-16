#!/bin/bash
SCRIPT_DIR=$(cd $(dirname "${BASH_SOURCE[0]}") && pwd)
cd $SCRIPT_DIR
export SONAR_TOKEN="17fc254547abfff6c001fd64c9322a4fc7778432"
/home/vmorantes/sonar-cloud/bin/sonar-scanner $@