# ResetSubmitted

With limesurvey : when using token answer persistence and allow update responses with token : when a particpant reload previous response : the response still submitted.

Then :
1. Survey administrator can not know if reponse is still valid
2. User can update some value to invalid respoinse : for example can set to empty a mandatory question.
3. When reload response : participant start at 1st page again and again.
4. No new confirmation or notification is sent the second time.

This plugin allow survey administrator to choose to reste response to not submitted when reloaded by token.

The token is not updated, then to know the 1xst submit date : you can use {TOKEN:COMPLETED}.
