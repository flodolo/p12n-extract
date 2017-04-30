#! /usr/bin/env bash

# Pretty printing functions
normal=$(tput sgr0)
green=$(tput setaf 2; tput bold)
yellow=$(tput setaf 3)
red=$(tput setaf 1)

function echored() {
    echo -e "${red}$*${normal}"
}

function echogreen() {
    echo -e "${green}$*${normal}"
}

function echoyellow() {
    echo -e "${yellow}$*${normal}"
}

if [ $# -lt 1 ]
  then
    red "ERROR: no arguments supplied."
    echo "Usage: update_data.sh *path-to-mozilla-unified*"
    exit 1
fi

# Get absolute path to /data folder from script relative path
cd $PWD/$(dirname "$0")/..
data_folder=$PWD/data
unified_path=$1

if [ ! -d $unified_path/.hg ]
then
    echored "Path to mozilla-unified doesn't exist or it's not a Mercurial repository"
    exit 1
fi

# Copy mozilla-* settings
branches+=( central beta release )
for branch in "${branches[@]}"
do
    echo "Updating mozilla-unified to bookmark ${branch}"
    hg -R $unified_path update $branch
    base_folder="${data_folder}/mozilla-${branch}"

    # Copy browser settings
    if [ ! -d "$base_folder/browser" ]
    then
        echo "Creating folder: $base_folder/browser"
        mkdir -p "$base_folder/browser"
    fi
    echo "Copying browser/search"
    cp -r "${unified_path}/browser/locales/search" "${base_folder}/browser"
    echo "Copying browser/searchplugins"
    cp -r "${unified_path}/browser/locales/searchplugins" "${base_folder}/browser"
    echo "Copying region.properties"
    cp -r "${unified_path}/browser/locales/en-US/chrome/browser-region" "${base_folder}/browser"

    # Copy mobile settings
    if [ ! -d "$base_folder/mobile" ]
    then
        echo "Creating folder: $base_folder/mobile"
        mkdir -p "$base_folder/mobile"
    fi
    echo "Copying mobile/search"
    cp -r "${unified_path}/mobile/locales/search" "${base_folder}/mobile"
    echo "Copying mobile/searchplugins"
    cp -r "${unified_path}/mobile/locales/searchplugins" "${base_folder}/mobile"
    if [ ! -d "$base_folder/mobile/browser-region" ]
    then
        echo "Creating folder: $base_folder/mobile/browser-region"
        mkdir -p "$base_folder/mobile/browser-region"
    fi
    echo "Copying region.properties"
    cp -r "${unified_path}/mobile/locales/en-US/chrome/region.properties" "${base_folder}/mobile/browser-region/"
done
