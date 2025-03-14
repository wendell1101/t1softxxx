MM_WEBHOOK_URL="https://talk.letschatchat.com/hooks/ep6r5uqtifrrme7ercyii3kcyw" #DeplayStaging
#MM_WEBHOOK_URL="https://talk.chatchat365.com/hooks/yi89y1kd6fgmf8gndisf4uocgc" #test
GITLAB_USER_LOGIN=$1
CLIENT_NAME=$2
CI_COMMIT_REF_NAME=$3
CI_JOB_ID=$4
CI_JOB_URL="https://git.smartbackend.com/sbs/og/-/jobs/$CI_JOB_ID"
CI_COMMIT_SHA=$5
CI_COMMIT_URL="https://git.smartbackend.com/sbs/og/commit/$CI_COMMIT_SHA"
MM_NAME="Deploy_Message"


if [ "$GITLAB_USER_LOGIN" = "mm.deploy" ]; then
	curl -i -X POST --data-urlencode 'payload={ "attachments": [ {"color": "#00ff33", "text":"'"Deploy #$CLIENT_NAME $CI_COMMIT_REF_NAME $CI_JOB_URL Finished"'"} ], "username":"'"$MM_NAME"'"}' $MM_WEBHOOK_URL
fi


#curl -i -X POST --data-urlencode 'payload={ "attachments": [ {"color": "#00ff33", "text":"'"Deploy #$CLIENT_NAME $CI_COMMIT_REF_NAME $CI_JOB_URL Succeeded"'"} ], "username": "${GITLAB_USER_LOGIN}"}' $MM_WEBHOOK_URL 
