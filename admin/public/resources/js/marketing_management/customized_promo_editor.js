(() => {
    class CustomizedPromoEditor {
        ERRORS = {
            "NOT_SUPPORT": "The {0} customized promo is not supported."
        }

        constructor() {
            this.modal = null
            this.container = null;
            this.form = null;

            this.condition_editor = null;
            this.release_editor = null;

            this.json = {};
            this.callback = null;
        }

        async init(callback) {
            this.modal = callback();
            this.container = this.modal[0].querySelector(".modal-body");
            this.form = this.container.querySelector("form");
            this.promo_select = this.container.querySelector("#CustomizedPromoList");
            this.promo_detail = this.container.querySelector("#CustomizedPromoDetail");

            this.promo_select.addEventListener("change", async () => {
                let result = await this.getDetail(this.promo_select.value);

                this.promo_detail.innerHTML = '';

                let condition_editor_container = this.container.querySelector("#CustomizedPromoConditionEditor .json-editor");
                let release_editor_container = this.container.querySelector("#CustomizedPromoReleaseEditor .json-editor");

                condition_editor_container.innerHTML = "";
                release_editor_container.innerHTML = "";

                this.condition_editor_container = null;
                this.release_editor_container = null;

                if(!result) {
                    return;
                }

                this.promo_detail.innerHTML = result.detail;

                this.condition_editor = this.createJSONEditor(condition_editor_container, {
                    "schema": result.schemas.condition
                }, JSON.parse(JSON.stringify(this.json)));

                this.release_editor = this.createJSONEditor(release_editor_container, {
                    "schema": result.schemas.release
                }, this.getOriginalValue("bonus_settings", []));
            });

            await this.load();
        }

        getError(key, ...props) {
            if(!Object.prototype.hasOwnProperty.call(this.ERRORS, key)) {
                return key.format(...props);
            }

            return this.ERRORS[key].format(...props);
        }

        getOriginalValue(name, default_value) {
            if(!this.json) {
                return default_value;
            }

            if(!Object.prototype.hasOwnProperty.call(this.json, name)) {
                return default_value;
            }

            return this.json[name];
        }

        async load() {
            this.list = await this.getList();
            
            let select = this.promo_select;
            select.innerHTML = "";
            select.appendChild(this.createOptionElement("", "-"));

            this.list.forEach((entry) => {
                select.appendChild(this.createOptionElement(entry.name, entry.title));
            });
        }

        async getList() {
            let response = await fetch("/marketing_management/list_customized_prom_rules");
            if(!response.ok) {
                return [];
            }

            let json = await response.json();

            if(typeof json !== "object" || !Object.prototype.hasOwnProperty.call(json, "success")) {
                return [];
            }

            return json.result;
        }

        async getDetail(class_name) {
            if(!class_name || class_name === "-") {
                return null;
            }

            const formData = new FormData();
            formData.append("class_name", class_name);

            let response = await fetch("/marketing_management/customized_prom_rule_detail", {
                method: "POST",
                body: formData
            });
            if(!response.ok) {
                return null;
            }

            let json = await response.json();

            if(typeof json !== "object" || !Object.prototype.hasOwnProperty.call(json, "success")) {
                return null;
            }

            return json.result;
        }

        isSupport(class_name) {
            if(!class_name) {
                return true;
            }

            let result = false;

            this.list.forEach((entry) => {
                result = (entry.name === class_name) ? true : result;
            });

            return result;
        }

        show(json, callback) {
            let run = () => {
                try {
                    this.json = (!!json) ? JSON.parse(json) : {};
                    this.callback = callback;
        
                    let class_name = this.getOriginalValue("class", "");
                    
                    if(!this.isSupport(class_name)) {
                        throw new Error(this.getError("NOT_SUPPORT", class_name));
                        return;
                    }

                    this.promo_select.value = (!class_name) ? "" : class_name;
                    this.promo_select.dispatchEvent(new Event("change"));
                    // console.log("show", this.json, this.callback, class_name);
        
                    this.modal.modal("show");
                } catch (err) {
                    this.json = {};
                    this.callback = null;
    
                    callback(false, json, err.message);
                }
            };

            this.load().then(run);
        }

        save() {
            this.callback(true, this.prepareValues());

            this.json = {};
            this.callback = null;
            this.modal.modal("hide");
        }

        prepareEditorValues(editor) {
            let schema = editor.schema;

            if(!Object.prototype.hasOwnProperty.call(schema, 'sort')) {
                return editor.getValue();
            }

            Object.keys(schema.sort).forEach((key) => {
                let option = schema.sort[key];
                let property_editor = editor.getEditor('root.' + key);
                let values = property_editor.getValue();

                let direction = (Object.prototype.hasOwnProperty.call(option, 'direction')) ? option['direction']: 'asc';
                if(!Object.prototype.hasOwnProperty.call(option, 'itemProperty')) {
                    return;
                }

                values.sort((entry1, entry2) => {
                    let type = (direction === 'asc') ? -1 : 1;
                    if ( entry1[option['itemProperty']] < entry2[option['itemProperty']] ){
                        return type;
                    }
                    if ( entry1[option['itemProperty']] > entry2[option['itemProperty']] ){
                        return type * -1;
                    }
                    return 0;
                });

                property_editor.setValue(values);
            });

            return editor.getValue();
        }

        prepareValues() {
            if(this.promo_select.value === "" || this.promo_select.value === "-") {
                return "";
            }

            let values = (!!this.json) ? JSON.parse(JSON.stringify(this.json)) : {};
            values["class"] = "";

            if(!!this.condition_editor) {
                let condition_values = this.prepareEditorValues(this.condition_editor);
                let condition_errors = this.condition_editor.validate(condition_values);

                if(condition_errors.length) {
                    let condition_error = condition_errors.shift();
                    throw new Error(this.condition_editor.getEditor(condition_error['path']).getTitle() + ": " + condition_error['message']);
                    return;
                }

                Object.assign(values, condition_values);
            }

            if(!!this.release_editor) {
                let release_values = this.prepareEditorValues(this.release_editor);
                let release_errors = this.release_editor.validate(release_values);
                
                if(release_errors.length) {
                    let release_error = release_errors.shift();
                    throw new Error(this.release_editor.getEditor(release_error['path']).getTitle() + ": " + release_error['message']);
                    return;
                }

                Object.assign(values, {
                    "bonus_settings": release_values
                });
            }

            values["class"] = this.promo_select.value;

            return values;
        }

        createOptionElement(value, text) {
            let element = document.createElement("option");
            element.setAttribute("value", value);
            element.innerHTML = text;

            return element;
        }

        createJSONEditor(container, override_options, values) {
            let json_editor_default_options = {
                theme: "bootstrap3",
                required_by_default: true,
                no_additional_properties: true,
                // disable_properties: true
            };

            let options = Object.assign({}, json_editor_default_options, override_options);

            if(!Object.prototype.hasOwnProperty.call(options, "schema")) {
                return null;
            }

            if(!options["schema"] || (Array.isArray(options["schema"]) && options["schema"].length <=0 )) {
                return null;
            }

            let editor = new JSONEditor(container, options);

            if(!!values) {
                editor.on('ready', () => {
                    editor.setValue(values);
                });
            }

            return editor;
        }
    }

    window.customized_promo_editor = new CustomizedPromoEditor();
})();