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
    echored "ERROR: no arguments supplied."
    echo "Usage: update_data.sh *path-to-mozilla-unified*"
    exit 1
fi

# Get absolute path to /data folder from script relative path
cd $PWD/$(dirname "$0")/..
root_folder=$PWD
data_folder=$PWD/data
unified_path=$1

if [ ! -d $unified_path/.hg ]
then
    echored "Path to mozilla-unified doesn't exist or it's not a Mercurial repository"
    exit 1
fi

branches+=( release beta central )
for branch in "${branches[@]}"
do
    echo "Updating mozilla-unified to bookmark ${branch}"
    hg -R $unified_path update $branch
    base_folder="${data_folder}/${branch}"

    # Firefox (/browser)
    folders=( browser-region search searchplugins )
    for folder in "${folders[@]}"
    do
        if [ ! -d "${base_folder}/browser/${folder}" ]
        then
            echo "Creating missing folder: ${base_folder}/browser/${folder}"
            mkdir -p "${base_folder}/browser/${folder}"
        fi
    done

    if [ -d "${unified_path}/browser/components/search/searchplugins" ]
    then
        echo "Removing existing browser/searchplugins folder"
        rm -r "${base_folder}/browser/searchplugins"
        echo "Copying browser/searchplugins"
        cp -r "${unified_path}/browser/components/search/searchplugins" "${base_folder}/browser"
        echo "Moving list.json in browser/search"
        mv "${base_folder}/browser/searchplugins/list.json" "${base_folder}/browser/search"
        echo "Copying region.properties"
        cp -r "${unified_path}/browser/locales/en-US/chrome/browser-region" "${base_folder}/browser"
    fi

    # Fennec (/mobile)
    folders=( browser-region search searchplugins )
    for folder in "${folders[@]}"
    do
        if [ ! -d "${base_folder}/mobile/${folder}" ]
        then
            echo "Creating missing folder: ${base_folder}/mobile/${folder}"
            mkdir -p "${base_folder}/mobile/${folder}"
        fi
    done

    if [ -d "${unified_path}/mobile/android/components/search/searchplugins" ]
    then
        echo "Removing existing mobile/searchplugins folder"
        rm -r "${base_folder}/mobile/searchplugins"
        echo "Copying mobile/searchplugins"
        cp -r "${unified_path}/mobile/android/components/search/searchplugins" "${base_folder}/mobile"
        echo "Moving list.json in mobile/search"
        mv "${base_folder}/mobile/searchplugins/list.json" "${base_folder}/mobile/search"
        echo "Copying region.properties"
        cp "${unified_path}/mobile/locales/en-US/chrome/region.properties" "${base_folder}/mobile/browser-region/"
    fi

    # Download comm-central settings
    if [ "${branch}" == "central" ]
    then
        base_url="https://hg.mozilla.org/comm-central/raw-file/default"
    else
        base_url="https://hg.mozilla.org/releases/comm-${branch}/raw-file/default"
    fi

    # Thunderbird (/mail)
    folders=( browser-region search searchplugins )
    for folder in "${folders[@]}"
    do
        if [ ! -d "${base_folder}/mail/${folder}" ]
        then
            echo "Creating missing folder: ${base_folder}/mail/${folder}"
            mkdir -p "${base_folder}/mail/${folder}"
        fi
    done
    wget -q "${base_url}/mail/locales/en-US/chrome/messenger-region/region.properties" -O "${base_folder}/mail/browser-region/region.properties"

    if [ "${branch}" == "release" ]
    then
        wget -q "${base_url}/mail/components/search/searchplugins/list.json" -O "${base_folder}/mail/search/list.json"
    fi

    searchplugins=( amazondotcom aol-web-search bing google twitter wikipedia yahoo )
    for sp in "${searchplugins[@]}"
    do
        if [ "${branch}" == "release" ]
        then
            wget -q "${base_url}/mail/components/search/searchplugins/${sp}.xml" -O "${base_folder}/mail/searchplugins/${sp}.xml"
        fi
    done

    # SeaMonkey
    folders=( browser-region searchplugins )
    for folder in "${folders[@]}"
    do
        if [ ! -d "${base_folder}/suite/${folder}" ]
        then
            echo "Creating missing folder: ${base_folder}/suite/${folder}"
            mkdir -p "${base_folder}/suite/${folder}"
        fi
    done
    wget -q "${base_url}/suite/locales/en-US/chrome/common/region.properties" -O "${base_folder}/suite/browser-region/region-common.properties"
    wget -q "${base_url}/suite/locales/en-US/chrome/browser/region.properties" -O "${base_folder}/suite/browser-region/region-browser.properties"
    wget -q "${base_url}/suite/locales/en-US/searchplugins/list.txt" -O "${base_folder}/suite/searchplugins/list.txt"
    searchplugins=( duckduckgo google wikipedia yahoo )
    for sp in "${searchplugins[@]}"
    do
        wget -q "${base_url}/suite/locales/en-US/searchplugins/${sp}.xml" -O "${base_folder}/suite/searchplugins/${sp}.xml"
    done
done

echo "Updating locales..."
${root_folder}/utils/update_locales.py
