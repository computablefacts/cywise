@once
<script>

  const onSuccessDefault = (result) => {
    if (toaster && result.msg) {
      toaster.toastSuccess(result.msg);
    }
  };

  const onErrorDefault = (error) => {
    if (toaster && error.message) {
      const errors = [];
      if (error.data) {
        for (const [attribute, messages] of Object.entries(error.data)) {
          if (Array.isArray(messages)) {
            errors.push(...messages);
          } else {
            errors.push(messages);
          }
        }
      }
      toaster.toastError(error.message + (errors.length > 0 ? "\n" + errors.join("\n") : ""));
    }
  };

  const onFinallyDefault = () => {
    //
  };

  function executeJsonRpcApiCall(method, params = {}, onSuccess = onSuccessDefault, onError = onErrorDefault,
                                 onFinally = onFinallyDefault) {
    axios.post('/api/v2/private/endpoint', {
      jsonrpc: "2.0", id: "1", method: method, params: params,
    }, {
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Accept-Encoding': 'gzip',
        'Authorization': 'Bearer {{ Auth::user()->sentinelApiToken() }}',
      }
    })
      .then(response => {
        if (response.data && response.data.error && onError) {
          onError(response.data.error);
        } else if (response.data && response.data.result && onSuccess) {
          onSuccess(response.data.result);
        } else {
          console.log(response);
        }
      })
      .catch(error => {
        if (toaster) {
          toaster.toastAxiosError(error);
        }
      })
      .finally(() => {
        if (onFinally) {
          onFinally();
        }
      });
  }

  function createInvitationsApiCall(users, onFinally = onFinallyDefault) {
    executeJsonRpcApiCall('invitations@create', {users: users}, onSuccessDefault, onErrorDefault, onFinally);
  }

  function createInvitationApiCall(fullname, email, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('invitations@create', {fullname: fullname, email: email}, onSuccess);
  }

  function sendInvitationApiCall(invitationId) {
    executeJsonRpcApiCall('invitations@send', {id: invitationId});
  }

  function testSshConnectionApiCall(serverId, ip, port, username) {
    executeJsonRpcApiCall('servers@testSshConnection', {server_id: serverId, ip: ip, port: port, username: username});
  }

  function configureServerApiCall(serverId, serverName, domain, ip, port, username) {
    executeJsonRpcApiCall('servers@configure',
      {server_id: serverId, name: serverName, domain: domain, ip: ip, port: port, username: username});
  }

  function deleteServerApiCall(serverId) {
    executeJsonRpcApiCall('servers@delete', {server_id: serverId});
  }

  function executeShellCommandApiCall(serverId, cmd, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('servers@executeShellCommand', {server_id: serverId, cmd: cmd}, onSuccess);
  }

  function getServerEventsApiCall(serverId) {
    executeJsonRpcApiCall('servers@events', {server_id: serverId});
  }

  function createNoteApiCall(note, scopes = [], onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('notes@create', {note: note, scopes: scopes}, onSuccess);
  }

  function deleteNoteApiCall(noteId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('notes@delete', {note_id: noteId}, onSuccess);
  }

  function toggleVulnerabilityVisibilityApiCall(uid, type, title) {
    executeJsonRpcApiCall('vulnerabilities@toggleVisibility', {uid: uid, type: type, title: title});
  }

  function dismissEventApiCall(eventId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('events@dismiss', {event_id: eventId}, onSuccess);
  }

  function createAssetApiCall(asset, monitor, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@create', {asset: asset, watch: monitor}, onSuccess);
  }

  function deleteAssetApiCall(assetId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@delete', {asset_id: assetId}, onSuccess);
  }

  function monitorAssetApiCall(assetId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@monitor', {asset_id: assetId}, onSuccess);
  }

  function unmonitorAssetApiCall(assetId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@unmonitor', {asset_id: assetId}, onSuccess);
  }

  function restartAssetScanApiCall(assetId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@restartScan', {asset_id: assetId}, onSuccess);
  }

  function tagAssetApiCall(assetId, tag, onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('assets@tag', {asset_id: assetId, tag: tag}, onSuccess, onError);
  }

  function untagAssetApiCall(assetId, tagId, onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('assets@untag', {asset_id: assetId, tag_id: tagId}, onSuccess, onError);
  }

  function listAllTagsApiCall(onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('assets@listTags', {}, onSuccess, onError);
  }

  function listAllGroupsApiCall(onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('assets@listGroups', {}, onSuccess, onError);
  }

  function degroupApiCall(group, onSuccess = onSuccessDefault, onError = onErrorDefault, onFinally = onFinallyDefault) {
    executeJsonRpcApiCall('assets@degroup', {group: group}, onSuccess, onError, onFinally);
  }

  function askCyberBuddyApiCall(threadId, directive, onSuccess = onSuccessDefault, onFinally = onFinallyDefault) {
    executeJsonRpcApiCall('cyberbuddy@ask', {thread_id: threadId, directive: directive}, onSuccess, onErrorDefault,
      onFinally);
  }

  function deleteConversationApiCall(conversationId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('cyberbuddy@delete', {conversation_id: conversationId}, onSuccess);
  }

  function saveActionSettingsApiCall(scopeType, scopeId, actions, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('cyberbuddy@saveActionSettings', {scope_type: scopeType, scope_id: scopeId, actions: actions},
      onSuccess);
  }

  function loadFrameworkApiCall(frameworkId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('frameworks@load', {framework_id: frameworkId}, onSuccess);
  }

  function unloadFrameworkApiCall(frameworkId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('frameworks@unload', {framework_id: frameworkId}, onSuccess);
  }

  function listTemplatesApiCall(onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('cyberscribe@listTemplates', {}, onSuccess);
  }

  function deleteTemplateApiCall(templateId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('cyberscribe@deleteTemplate', {template_id: templateId}, onSuccess);
  }

  function saveTemplateApiCall(templateId, isModel, name, blocks, onSuccess = onSuccessDefault,
                               onFinally = onFinallyDefault) {
    executeJsonRpcApiCall('cyberscribe@saveTemplate',
      {template_id: templateId, is_model: isModel, name: name, blocks: blocks}, onSuccess, onErrorDefault, onFinally);
  }

  function listTablesApiCall(onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@list', {}, onSuccess);
  }

  function listLocalBucketContentApiCall(onSuccess = onSuccessDefault) {
    listAwsBucketContentApiCall('local-region', 'local-access_key_id', 'local-secret_access_key', 'local-input',
      'local-output', onSuccess);
  }

  function listAwsBucketContentApiCall(region, access_key_id, secret_access_key, input_folder, output_folder,
                                       onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@listBucketContent', {
      'storage': 's3',
      'region': region,
      'access_key_id': access_key_id,
      'secret_access_key': secret_access_key,
      'input_folder': input_folder,
      'output_folder': output_folder,
    }, onSuccess);
  }

  function listAzureBucketContentApiCall(connection_string, input_folder, output_folder, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@listBucketContent', {
      'storage': 'azure',
      'connection_string': connection_string,
      'input_folder': input_folder,
      'output_folder': output_folder,
    }, onSuccess);
  }

  function listLocalFileContentApiCall(tables, onSuccess = onSuccessDefault) {
    listAwsFileContentApiCall('local-region', 'local-access_key_id', 'local-secret_access_key', 'local-input',
      'local-output', tables, onSuccess);
  }

  function listAwsFileContentApiCall(region, access_key_id, secret_access_key, input_folder, output_folder, tables,
                                     onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@listFileContent', {
      'storage': 's3',
      'region': region,
      'access_key_id': access_key_id,
      'secret_access_key': secret_access_key,
      'input_folder': input_folder,
      'output_folder': output_folder,
      'tables': tables,
    }, onSuccess);
  }

  function listAzureFileContentApiCall(connection_string, input_folder, output_folder, tables,
                                       onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@listFileContent', {
      'storage': 'azure',
      'connection_string': connection_string,
      'input_folder': input_folder,
      'output_folder': output_folder,
      'tables': tables,
    }, onSuccess);
  }

  function importLocalFileApiCall(tables, updatable, copy, deduplicate, description, onSuccess = onSuccessDefault) {
    importAwsFileApiCall('local-region', 'local-access_key_id', 'local-secret_access_key', 'local-input',
      'local-output', tables, updatable, copy, deduplicate, description, onSuccess);
  }

  function importAwsFileApiCall(region, access_key_id, secret_access_key, input_folder, output_folder, tables,
                                updatable, copy, deduplicate, description, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@import', {
      'storage': 's3',
      'region': region,
      'access_key_id': access_key_id,
      'secret_access_key': secret_access_key,
      'input_folder': input_folder,
      'output_folder': output_folder,
      'tables': tables,
      'updatable': updatable,
      'copy': copy,
      'deduplicate': deduplicate,
      'description': description,
    }, onSuccess);
  }

  function importAzureFileApiCall(connection_string, input_folder, output_folder, tables, updatable, copy, deduplicate,
                                  description, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@import', {
      'storage': 'azure',
      'connection_string': connection_string,
      'input_folder': input_folder,
      'output_folder': output_folder,
      'tables': tables,
      'updatable': updatable,
      'copy': copy,
      'deduplicate': deduplicate,
      'description': description,
    }, onSuccess);
  }

  function executeSqlQueryApiCall(query, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@executeSqlQuery', {query: query, store: false}, onSuccess);
  }

  function createVirtualTableApiCall(query, materialize, name, description, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@executeSqlQuery',
      {query: query, store: true, materialize: materialize, name: name, description: description}, onSuccess);
  }

  function promptToQueryApiCall(prompt, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@promptToQuery', {prompt: prompt}, onSuccess);
  }

  function forceTableImportApiCall(tableId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@forceImport', {table_id: tableId}, onSuccess);
  }

  function updateTableDescriptionApiCall(name, description, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('tables@updateDescription', {name: name, description: description}, onSuccess);
  }

  function toggleGetsAuditReportApiCall(userId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('users@toggleGetsAuditReport', {user_id: userId}, onSuccess);
  }

  function sendAuditReportApiCall(userId, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('users@sendAuditReport', {user_id: userId}, onSuccess);
  }

  function deletePromptApiCall(promptId) {
    executeJsonRpcApiCall('prompts@delete', {prompt_id: promptId});
  }

  function updatePromptApiCall(promptId, template) {
    executeJsonRpcApiCall('prompts@update', {prompt_id: promptId, template: template});
  }

  function deleteCollectionApiCall(collectionId) {
    executeJsonRpcApiCall('collections@delete', {collection_id: collectionId});
  }

  function updateCollectionApiCall(collectionId, priority) {
    executeJsonRpcApiCall('collections@update', {collection_id: collectionId, priority: priority});
  }

  function listCollectionsApiCall(onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('collections@list', {}, onSuccess);
  }

  function deleteChunkApiCall(chunkId) {
    executeJsonRpcApiCall('chunks@delete', {chunk_id: chunkId});
  }

  function updateChunkApiCall(chunkId, value) {
    executeJsonRpcApiCall('chunks@update', {chunk_id: chunkId, value: value});
  }

  function deleteFileApiCall(fileId) {
    executeJsonRpcApiCall('files@delete', {file_id: fileId});
  }

  function deleteOsqueryRuleApiCall(ruleId) {
    executeJsonRpcApiCall('osquery@delete', {rule_id: ruleId});
  }

  function createOsqueryRuleApiCall(name, description, category, platform, interval, is_ioc, score, query,
                                    onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('osquery@create', {
      name: name,
      description: description,
      category: category,
      platform: platform,
      interval: interval,
      is_ioc: is_ioc,
      score: score,
      query: query
    }, onSuccess);
  }

  function deleteOssecRuleApiCall(ruleId) {
    executeJsonRpcApiCall('ossec@delete', {rule_id: ruleId});
  }

  function createOssecRuleApiCall(name, description, rationale, remediation, platform, rule,
                                  onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('ossec@create', {
      name: name,
      description: description,
      rationale: rationale,
      remediation: remediation,
      platform: platform,
      rule: rule,
    }, onSuccess);
  }

  function listRolesAndPermissionsApiCall(onSuccess, onFinally) {
    executeJsonRpcApiCall('roles@list', {}, onSuccess, onErrorDefault, onFinally);
  }

  function addPermissionToRoleApiCall(role, permission, onFinally) {
    executeJsonRpcApiCall('roles@addPermission', {role: role, permission: permission}, onSuccessDefault, onErrorDefault,
      onFinally);
  }

  function removePermissionFromRoleApiCall(role, permission, onFinally) {
    executeJsonRpcApiCall('roles@removePermission', {role: role, permission: permission}, onSuccessDefault,
      onErrorDefault, onFinally);
  }

  function createUserInvitationApiCall(email, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('invitations@create', {email: email}, onSuccess);
  }

  function shareAssetApiCall(tags, email, onSuccess = onSuccessDefault) {
    executeJsonRpcApiCall('assets@share', {tags: tags, email: email}, onSuccess);
  }

  function toggleScheduledTaskApiCall(taskId, enabled = null, onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('scheduled-tasks@toggle', {task_id: taskId, enabled: enabled}, onSuccess, onError);
  }

  function deleteScheduledTaskApiCall(taskId, onSuccess = onSuccessDefault, onError = onErrorDefault) {
    executeJsonRpcApiCall('scheduled-tasks@delete', {task_id: taskId}, onSuccess, onError);
  }

</script>
@endonce