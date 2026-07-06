#!/bin/bash
set -euo pipefail

cd "$(dirname "$0")"
jar_file="vikunja-team-mapper.jar"
rm -f "$jar_file"
cd vikunja-team-mapper
zip -vr "../$jar_file" vikunja-mapper.js META-INF/
echo "Created $jar_file"
