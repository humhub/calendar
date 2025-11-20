#!/bin/bash

for filename in *.yaml; do
    echo "--------- $filename ---------------------"
    npx @redocly/cli build-docs $filename -o $(basename "$filename" .yaml).html
done