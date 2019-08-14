if (!window.ShipperHQ) {
    window.ShipperHQ = {};
}

/**
 * Init data for loading jQuery
 * 
 * @param versionRequires
 * @param urlPlaceholders
 * @constructor
 */
window.ShipperHQ.JQueryLoader = function (versionRequires, urlPlaceholders) {
    this.versionMap = versionRequires;
    this.urlPlaceholders = urlPlaceholders;
    ShipperHQ.JQueryLoader.__instance = this;
    this.loadJquery();
};

/**
 * Tries to load jquery if it is not loaded
 */
ShipperHQ.JQueryLoader.prototype.loadJquery = function () {
    this.loaded = false;
    if (window.jQuery) {
        this.jquery = window.jQuery;
        window.$j = this.jquery.noConflict();
        if (window.jQuery.ui) {
            this.loaded = true;
            ShipperHQ.JQueryLoader.executeCallbacks();
        } else {
            this.requireUi();
        }
    } else {
        this.requireJquery();
    }
};

/**
 * Requires script tag with jquery
 * 
 */
ShipperHQ.JQueryLoader.prototype.requireJquery = function () {
    var realVersion = this.versionMap['jquery']['default'];
    this.requireJavaScript(this.urlPlaceholders['jquery'].replace('{version}', realVersion), function () {
        this.jquery = window.jQuery;
        window.$j = this.jquery.noConflict();
        this.requireUi();
    });
};

/**
 * Requires JQuery UI script tag with styles
 */
ShipperHQ.JQueryLoader.prototype.requireUi = function () {
    var version = 'default';
    if (this.jquery) {
         version = this.jquery().jquery.split('.').slice(0, 2).join('.');
    }
    
    if (!this.versionMap['ui'][version]) {
        version = 'default';
    }
    
    var realVersion = this.versionMap['ui'][version];
    var scriptUrl = this.urlPlaceholders['ui'].replace('{version}', realVersion);
    var styleUrl = this.urlPlaceholders['ui-style'].replace('{version}', realVersion);
    this.requireStyleSheet(styleUrl);
    this.requireJavaScript(scriptUrl, function () {
        this.loaded = true;
        ShipperHQ.JQueryLoader.executeCallbacks();
    });
};

ShipperHQ.JQueryLoader.prototype.requireJavaScript = function (url, callback, styleUrl) {
    var script = document.createElement("script");
    script.type = "text/javascript";
    var loadScope = this;

    if (script.readyState){  //IE
        script.onreadystatechange = function() {
            var state = script.readyState
            if (state == "loaded" ||
                state == "complete"){
                script.onreadystatechange = null;
                callback.call(loadScope);
            }
        };
    } else {  //Others
        script.onload = function(){
            callback.call(loadScope);
        };
    }

    script.src = url;
    this.addHeadElement(script);
};

window.ShipperHQ.JQueryLoader.prototype.requireStyleSheet = function (url) {
    var styleElement = document.createElement("link");
    styleElement.rel = 'stylesheet';
    styleElement.href = url;

    this.addHeadElement(styleElement);
};

window.ShipperHQ.JQueryLoader.prototype.addHeadElement = function (element) {
    var head = document.getElementsByTagName('head')[0];
    if (head) {
        head.appendChild(element);
    }
};

window.ShipperHQ.JQueryLoader.prototype.isLoaded = function () {
    return this.loaded;
};

ShipperHQ.JQueryLoader.callbacks = [];

ShipperHQ.JQueryLoader.tryCallback = function (callback) {
    if (!this.instance() || !this.instance().isLoaded()) {
        this.callbacks.push(callback);
    } else {
        callback(this.instance().jquery());
    }
};

ShipperHQ.JQueryLoader.executeCallbacks = function () {
    if (this.instance().isLoaded()) {
        while (this.callbacks.length) {
            var callback = this.callbacks.shift();
            callback(this.instance().jquery());
        }
    }
};

ShipperHQ.JQueryLoader.__instance = false;

ShipperHQ.JQueryLoader.instance = function () {
    if (!this.__instance) {
        return false;
    }
    
    return this.__instance;
};

window.deferredShipperHQCode = function (callback) {
    ShipperHQ.JQueryLoader.tryCallback.call(ShipperHQ.JQueryLoader, callback);
};

