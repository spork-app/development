export default {
    state: {
        files: Spork.getLocalStorage('developer_files', []),
        open: Spork.getLocalStorage('developer_open_state', {
            feature_id: null,
            tab: null,
        }),
        filesOpen: {},
        editor: null,
        loadingFiles: false,
    },
    getters: {
        tab: (state, getters,) => getters.openFiles[state.open.tab],
        openProject: (state, getters, globalState) => globalState.Feature.features.filter(feature => feature.id === state.open.feature_id)[0],
        file(state) {
            if (!state.openFile) {
                return null;
            }

            return state.files[state.openFile];
        },
        files(state) {
            const files = state.files.reduce((files, file) => {
                return {
                    ...files,
                    [file.absolute]: file,
                }
            }, {});

            return files;
        },
        openFiles(state) {
            return state.filesOpen;
        },
        editor: (state) => state.editor,
        loadingFiles: (state) => state.loadingFiles,
    },
    mutations: {
        setOpen(state, data) {
            if (state.editor) {
                state.editor.getSession().setUndoManager(new ace.UndoManager())
            }
            state.open = Spork.setLocalStorage('developer_open_state', data);
        },
        setFiles(state, data) {
            state.files = Spork.setLocalStorage('developer_files', data);
        },
        openFile(state, { data, file}) {
            if (typeof data !== 'string') {
                data = JSON.stringify(data, null, 4);
            }

            state.filesOpen = {
                ...state.filesOpen,
                [file.absolute]: {
                    ...file,
                    data,
                    originalData: data,
                    isDirty: false,
                }
            }
            state.open.tab = file.absolute;
            const modeList = ace.require("ace/ext/modelist")

            let expectedMode = modeList.getModeForPath(file.name)
            ace.require(expectedMode.name);
            state.editor?.getSession()?.setMode(expectedMode.name)

            if (!['coffee', 'css','html','javascript','json','lua','php','xml','xquery'].includes(expectedMode.name)) {
                return;
            }

            try {        
                ace.config.setModuleUrl('ace/mode/' + expectedMode.name +'_worker', require('file-loader?esModule=false!ace-builds/src-noconflict/worker-' + expectedMode.name + '.js'));
            } catch (e) {
                console.error(e);
                // Not every language type will have a worker, but that doesn't mean we can't try to load it.
            }
        },
        closeFile(state, file) {
            let files = state.filesOpen;
            delete files[file.absolute]
            state.filesOpen = files;

            if (state.open.tab === file.absolute && Object.keys(state.filesOpen).length > 0) {
                state.open.tab = Object.keys(state.filesOpen)[0];
                Spork.setLocalStorage('developer_open_state', state.open)
            }
        },
        updateText(state, { file, data }) {
            state.filesOpen[file.absolute].data = data;
            state.filesOpen[file.absolute].isDirty = state.filesOpen[file.absolute].originalData !== data;
        }
    },
    actions: {
        openProject({ commit, getters, state }, { id, name, path }) {
            commit('setFiles', [])
            commit('setOpen', {
                ...state.open,
                feature_id: id,
                path,
            })
            state.loadingFiles = true
            state.filesOpen = {};
            // once we open a project, we need to fetch the files.
            axios.post('/api/files/' + id, {
                path,
            })
                .then(({ data }) => {
               

                    commit('setFiles', data);
                })
                .catch(e => console.error(e.message))
                .finally(() => {
                    setTimeout(() => state.loadingFiles = false, 250);
                })
        },
        openFile({ state, getters, commit }, { path, id, file }) {
            axios.post('/api/files/' + id, {
                path,
            })
            .then(({ data }) => {
                commit('openFile', { data, file })
                // Bus.$emit('fileChanged', file);
            })
        },
        async saveOpenFile({ state, getters, commit}, file) {
            await axios.put('/api/files/' + file.feature_id, {
                path: file.file_path,
                data: file.data
            })
            
            state.filesOpen = {
                ...state.filesOpen,
                [file.absolute]: {
                    ...(state.filesOpen[file.absolute] ?? {}),
                    originalData: file.data,
                    isDirty: false,
                }
             }
            Spork.toast('Saved ' + file.file_path.split('/').reverse()[0])
        },
        async createDirectory({ dispatch, commit, state }, { name }) {
            await axios.post('/api/files/' + state.open.feature_id+'/create-directory', {
                 name,
            });

            await dispatch('openProject', {
                id: state.open.feature_id,
                path: state.open.path
            })
        },
        async createFile({ dispatch, commit, state }, { name }) {
            await axios.post('/api/files/' + state.open.feature_id+'/create-file', {
                name,
            });

            await dispatch('openProject', {
                id: state.open.feature_id,
                path: state.open.path
            })
        },
        async destroyFileOrDirectory({ dispatch, commit, state }, file) {
            await axios.post('/api/files/' + state.open.feature_id+'/destroy', {
                name: file.file_path,
            });

            await dispatch('openProject', {
                id: state.open.feature_id,
                path: state.open.path
            })
        },
        async redeploy({ dispatch, commit, state }, file) {
            await axios.post('/api/feature-list/' + state.open.feature_id+'/redeploy');

            await dispatch('openProject', {
                id: state.open.feature_id,
                path: state.open.path
            })
        },
        async setupEditor({ state, getters, commit, dispatch }, { editor }) {
            state.editor = editor;

            editor.commands.addCommand({
                name: "saveFile",
                bindKey: {win: "Ctrl-s", mac: "Command-s"},
                async exec(e) {
                    await dispatch('saveOpenFile', getters.tab);
                    editor.session.getUndoManager().markClean()
                }
            })
        }
    },
}