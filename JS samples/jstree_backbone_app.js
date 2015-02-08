window.groupsTreeApp = window.groupsTreeApp || {
    Models  : {},
    Collections : {},
    Views   : {},
    Routers : {},
    started : false,
    membersPage : 1,
    path    : window.location.pathname.split('.php',1)+'.php',
    init    : function() {
        Backbone.history.stop();
        Backbone.history.start();
    }
};

$(function() {
     // Raw Data Model and Collection
    window.groupsTreeApp.Models.Organization = Backbone.Model.extend({
        urlRoot : window.groupsTreeApp.path+'/api/xxxxx',
        idAttribute : "id",
        defaults : function () {
            return {
                'id'        : 0,
                'parent_id':'',
                'parentId':'',
                'name'      : '',
                'text'  : '',
                'lft'       : 0,
                'rgt'       : 0,
                'level'     : 0,
                'root'      : 0,
                'limit_plorg_id' : '',
                'plorg_id' : '',
                'description': ''
            };
        },
        initialize : function () {
            Backbone.Model.prototype.initialize.apply(this, arguments);
        }
    });

    // Rendered Data Model and Collection
    window.groupsTreeApp.Models.Treenode = Backbone.Model.extend({
        urlRoot : 'xxxx',
        idAttribute : "id",
        defaults : function () {
            return {
                'id'        : 0,
                'name'      : '',
                'text'      : '',
                'lft'       : 0,
                'rgt'       : 0,
                'level'     : 0,
                'parent_id':'',
                'parentId':'',
                'root'      : 0,
                'limit_plorg_id' : '',
                'plorg_id' : '',
                'description': '',
                'modifydate': ''
            };
        },
        initialize : function () {
            Backbone.Model.prototype.initialize.apply(this, arguments);
        }
    });

    window.groupsTreeApp.Collections.Treenode = Backbone.Collection.extend({
        model : window.groupsTreeApp.Models.Treenode,
        url : 'xxxx',
        initialize : function () {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },
        parse:function(results){
            $.each(results, function(index, val) {
                 /* grid data to pass back */
                 var desc = "";
                 if(val.description != null){
                    desc = val.description;
                 }
                 val.data = {"description":desc};
            });
            return results;
        },
        moveNode : function (evt, context) {
            var self = this;
            if (evt.hasOwnProperty("type") && evt.type == 'move_node') {
                if (context.hasOwnProperty("node") && context.node.hasOwnProperty("id")) {
                    if (context.node.id == window.selectedNodeId) {
                        notify("warning","Warning","The root node of this set can not be moved.", null);
                        evt.stopImmediatePropagation();
                        return false;
                    }
                    else {
                        if(context.node.parent != self.undoMove){
                            $('#groups-tree').jstree('open_node',context.node.parent);
                            var myModel = window.groupsTreeApp.org.el.itemList.get(context.node.id);
                            if(myModel != 'undefined' && myModel != null){
                                if(context.node.parent == "#"){
                                    context.node.parent = null;
                                }
                                myModel.set("parent_id", context.node.parent);
                                myModel.set("parentId", context.node.parent);
                                myModel.set("limit_plorg_id", window.selectedNodeId);
                                myModel.set("description", context.description);
                                myModel.set("plorg_id", window.selectedNodeId);
                                myModel.save({},{
                                    error :function(e){
                                        self.undoMove = context.old_parent;
                                        $('#groups-tree').jstree("move_node",context.node,context.old_parent);
                                        notify("error","Error","There has been an error moving the node "+context.node.text,null);
                                        window.groupsTreeApp.org.initialize();
                                    },
                                    success : function(model, response){
                                        if(apiResponseCheck("Moved group", "Could not move group", response, 4000)){
                                            var objId = $(this).attr('aria-activedescendant');
                                            self.undoMove = null;
                                        }else{
                                            self.undoMove = context.old_parent;
                                            $('#groups-tree').jstree("move_node",context.node,context.old_parent);
                                            window.groupsTreeApp.org.initialize();
                                        }
                                    }
                                });
                            }else{
                                self.undoMove = context.old_parent;
                                $('#groups-tree').jstree("move_node",context.node,context.old_parent);
                                notify("error","Error","There has been an error moving the node "+context.node.text,null);
                                window.groupsTreeApp.org.initialize();
                            }
                        }
                        else
                        {
                            evt.stopImmediatePropagation();
                            self.undoMove = null;
                        }
                    }
                }
            }
        },
        createNode : function (evt, context) {
            var self = this;
            if (evt.hasOwnProperty("type") && evt.type == 'create_node') {
                evt.stopImmediatePropagation();
                if (context.hasOwnProperty("node") && context.node.hasOwnProperty("parent") && (context.node.id.match(/group\-no\-nodes/g) == null || context.node.id.match(/group\-no\-nodes/g) == "")) {
                    bootbox.dialog({
                        message:"<form><div class='form-group'><label class='control-label'>Name:</label> <input name=\"newName\" id='form-focus' class='form-control' /></div><div class='form-group'><label class='control-label'>Description:</label> <input name=\"description\" class='form-control' /></div><input type='submit' style='display:none;'/></form><script>$('form').on('submit',function(){$('.btn-primary').click();return false;});</script>",
                        title: "Please enter a new group.",
                        buttons:{
                            cancel: {
                                label: "Cancel",
                                className: "btn-cancel",
                                callback: function(){
                                    $('#groups-tree').jstree('delete_node',context.node);
                                }
                            },
                            create: {
                                label: "Create",
                                className: "btn btn-primary",
                                callback: function(e){
                                    var newName = $(e.target).parents('.modal-dialog').find('form input[name=newName]').val();
                                    var description = $(e.target).parents('.modal-dialog').find('form input[name=description]').val();
                                    if (newName === null || newName == 'undefined' || newName == '') {
                                        $(e.target).parents('.modal-dialog').find('form input[name=newName]').parent().addClass('has-error');
                                        $(e.target).parents('.modal-dialog').find('.help-block').remove();
                                        $(e.target).parents('.modal-dialog').find('form input[name=newName]').parent().append('<span class="help-block">Name is required</span>');
                                        // $('#groups-tree').jstree('delete_node',context.node);
                                        e.stopImmediatePropagation();
                                        return false;
                                    } else {
                                        $('#groups-tree').jstree('open_node',context.node.parent);
                                        $('#groups-tree').jstree('set_text',context.node,newName+' (Saving...)');
                                        $('#groups-tree').jstree('delete_node', $('#groups-tree').jstree('get_node','group-no-nodes'));
                                        var myModel = new window.groupsTreeApp.Models.Organization;
                                        myModel.set("id", null);
                                        myModel.set("parent_id", context.node.parent);
                                        myModel.set("name", newName);
                                        myModel.set("description", description);
                                        myModel.set("text", newName);
                                        myModel.set("plorg_id", window.selectedNodeId);
                                        myModel.set("limit_plorg_id", window.selectedNodeId);
                                        myModel.save({url: window.groupsTreeApp.path+'/api/xxxx'}, {
                                            success: function(e, f){
                                                if(apiResponseCheck("Created group", "Could not create group", f, 8000)){
                                                    context.node.data = {"description":description};
                                                    context.node.text = newName;
                                                    $('#groups-tree').jstree('set_id',context.node,f.data.id);
                                                    $('#groups-tree').jstree('set_text',context.node,newName);
                                                    $('#groups-tree').jstree('sort');
                                                    window.groupsTreeApp.org.initialize();
                                                }else{
                                                    $('#groups-tree').jstree('delete_node',context.node);
                                                }
                                            },
                                            error :function(){
                                                notify("error","Error","There has been an error creating the node...",null);
                                                window.groupsTreeApp.org.initialize();
                                            }
                                        });
                                    }
                                }
                            }
                        }
                    }).after(function(){
                        setTimeout("$('.modal-dialog').find('form input[name=newName]').focus()",500 );
                        $('')
                    });
                }
            }
        },
        renameNode : function (evt, context) {
            if (evt.hasOwnProperty("type") && evt.type == 'rename_node') {
                if (context.hasOwnProperty("node") && context.node.hasOwnProperty("id")) {
                    if (context.node.id == '') {
                        // NOOP, as temp id isn't valid
                        evt.stopImmediatePropagation();
                    }else {
                        if (context.text === null || context.text == 'undefined' || $.trim(context.text) == '') {
                            $(evt.target).parents('.modal-body').find('.help-block').remove();
                            $(evt.target).parents('.modal-body').find('form input').parent().append('<span class="help-block">Name is required</span>');
                            evt.stopImmediatePropagation();
                            return false;
                        }else{
                            $('#groups-tree').jstree('set_text',context.node,context.text+' (Saving...)');
                            var myModel = window.groupsTreeApp.org.el.itemList.get(context.node.id);
                            myModel.set({"name": context.text});
                            myModel.set({"text": context.text});
                            myModel.set("plorg_id", window.selectedNodeId);
                            myModel.set("description", context.node.data.description);
                            myModel.set("limit_plorg_id", window.selectedNodeId);
                            myModel.save({url: window.groupsTreeApp.path+'/xxxx'},{
                                success:function(e,r){
                                    if(!apiResponseCheck("Renamed group", "Could not rename group", r, 4000)){
                                        $('#groups-tree').jstree('sort');
                                        window.groupsTreeApp.org.initialize();
                                    }else{
                                        $('#groups-tree').jstree('set_text',context.node,context.text);
                                        $('#groups-tree').jstree('sort');
                                    }
                                },
                                error:function(e,r){
                                    notify("error","Error","There was an error renaming the node..."+$.parseJSON(r.responseText).message,8000);
                                    // notify("error","Error Occurred",$.parseJSON(r.responseText).message,0);
                                    window.groupsTreeApp.org.initialize();
                                }
                            });
                        }
                    }
                }
            }
        },
        refreshNode:function(evt,context){
            if (context.hasOwnProperty("node") && context.node.hasOwnProperty("id")) {
                    if (context.node.id == '') {
                        // NOOP, as temp id isn't valid
                        console.log('invalid');
                        evt.stopImmediatePropagation();
                    }else {

                        if (context.node.text === null || context.node.text == 'undefined' || $.trim(context.node.text) == '') {
                            evt.stopImmediatePropagation();
                            return false;
                        }else{
                            var realName = context.node.text;
                            $('#groups-tree').jstree('set_text',context.node,context.node.text+' (Saving...)');
                            var myModel = window.groupsTreeApp.org.el.itemList.get(context.node.id);
                            myModel.set({"name": realName});
                            myModel.set({"text": realName});
                            myModel.set("plorg_id", window.selectedNodeId);
                            myModel.set("description", context.node.data.description);
                            myModel.set("limit_plorg_id", window.selectedNodeId);
                            myModel.save({url: window.groupsTreeApp.path+'/xxxx'},{
                                success:function(e,r){
                                    if(!apiResponseCheck("Updated group", "Could not update group", r, 4000)){
                                        console.log('error renaming node');
                                        $('#groups-tree').jstree('sort');
                                        window.groupsTreeApp.org.initialize();
                                    }else{
                                        $('#groups-tree').jstree('set_text',context.node,realName);
                                    }
                                },
                                error:function(e,r){
                                    notify("error","Error","There was an error updating the node..."+$.parseJSON(r.responseText).message,8000);
                                    window.groupsTreeApp.org.initialize();
                                }
                            });
                        }
                    }
                }
        },
        deleteNode : function (evt, context) {
            clearInfoPanel();
            $('#rnm-gp-node').addClass('disabled');
            $('#del-gp-node').addClass('disabled');
            if (evt.hasOwnProperty("type") && evt.type == 'delete_node') {
                if((context.node.id.match(/j2_/g) == "" || context.node.id.match(/j2_/g) == null) && (context.node.id.match(/group\-no\-nodes/g) == null || context.node.id.match(/group\-no\-nodes/g) == "")){
                    bootbox.confirm("Are you sure you want to delete the group "+context.node.text+"?", function(result) {
                        if (result) {
                            if (context.hasOwnProperty("node") && context.node.hasOwnProperty("id")) {
                                // Can't edit own node
                                if (context.node.id == window.selectedNodeId) {
                                    notify("warning","Warning","The root node of this set can not be deleted.",null);
                                    evt.stopImmediatePropagation();
                                    return false;
                                } else {
                                    if($('#groups-tree').find(".jstree-node").length > 0){
                                        var myModel = window.groupsTreeApp.org.el.itemList.get(context.node.id);
                                        if(myModel != 'undefined' && myModel != null && myModel != ''){
                                            myModel.destroy({
                                                success:function(e,r){
                                                    if(!apiResponseCheck("Deleted group", "Could not delete group", r, 4000)){
                                                        $('#groups-tree').jstree('refresh');
                                                    }
                                                },
                                                error:function(e,r){
                                                    notify("error","Error","There was an error deleting the node..."+$.parseJSON(r.responseText).message,null);
                                                }
                                            });
                                        }
                                    }else{
                                        $('#groups-tree').jstree('create_node','#', $.parseJSON('{"id":"group-no-nodes","text":"No groups found for this organization"}'),0);
                                    }
                                }
                            }
                        }else{
                            $('#groups-tree').jstree('refresh');
                        }
                    });
                }
            }
        },
        nodeChanged : function (evt, context) {
            if (evt.hasOwnProperty("type") && evt.type == 'changed' && context.hasOwnProperty('action') && context.action == "select_node") {
                if (context.hasOwnProperty("node") && context.node.hasOwnProperty("id")) {
                    // Find children
                    if (context.node.id == window.selectedNodeId || context.node.id == '#') {
                        notify("warning","Warning","The root node of this set can not be deleted.", null);
                        evt.stopImmediatePropagation();
                        return false;
                    }
                    else {

                    }
                }
            }
        }
    });

    window.groupsTreeApp.Collections.Organization = Backbone.Collection.extend({
        model : window.groupsTreeApp.Models.Organization,
        url : 'group_url',
        initialize : function () {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        }
    });

    window.groupsTreeApp.Collections.GroupMembers = Backbone.Collection.extend({
        url : '/api/xxxx',
        response: null,
        initialize : function () {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },
        parse:function(response){
            if(response.status == "ZERO_RESULTS"){
                return null;
            }
            this.response = response;
            return response.data;
        },
        getTotal:function(){
            if(this.response != 'undefined' && this.response != null){
                return this.response.totalitems;
            }else{
                return null;
            }
        }
    });

    window.groupsTreeApp.Collections.GroupDetail = Backbone.Collection.extend({
        url : '/api/xxxx',
        initialize : function () {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },
        parse:function(response){
            if(response.status == "ZERO_RESULTS"){
                return null;
            }
            return response.data;
        }
    });

    window.groupsTreeApp.Views.Treenode = Backbone.View.extend({
        el : $('#groups-tree'),
        model : new groupsTreeApp.Models.Treenode,
        collection : new groupsTreeApp.Collections.Treenode,
        itemList : new groupsTreeApp.Collections.Organization,
        groupMembers: new groupsTreeApp.Collections.GroupMembers,
        limit: 15,
        render: function() {
            var self = this;
            window.groupsTreeApp.membersPage = 1;
            this.$el.on("select_node.jstree",function(e, o){
                $('#rnm-gp-node').removeClass('disabled');
                $('#del-gp-node').removeClass('disabled');
                self.detailGroup(e, o);
            }).jstree({
                "plugins" : [ "contextmenu", "unique", "dnd", "sort"],
                "core" : {
                    "data" : function(node, cb) {
                        var myName = '';
                        var myId = '#';
                        if (node.id == '#') {
                                // myName = "Top-level Organization";
                                // cb.call(this, [{text: myName, parent: myId, children:eval(JSON.stringify(self.collection))}]);
                                var that = this;
                                self.collection.fetch({
                                    data : $.param({plorg_id : window.selectedNodeId}),
                                    success:function(results){
                                        if(results.length > 0){
                                            cb.call(that, eval(JSON.stringify(results)));
                                        }else{
                                            cb.call(that, $.parseJSON('{"id":"group-no-nodes","name":"No groups found for this organization","text":"No groups found for this organization", "description":"test"}'));
                                        }
                                    },
                                    error: function(m,r) {
                                        notify("error","Error Occurred",$.parseJSON(r.responseText).message,0);
                                        console.log($.parseJSON(r.responseText).message);
                                        // self.$el.html("<div class='alert alert-danger'><strong>Error</strong>: "+$.parseJSON(r.responseText).message+"</div>");
                                    }
                                });
                        } else {
                            var that = this;
                            self.collection.fetch({
                                data : $.param({id : node.id,plorg_id : window.selectedNodeId}),
                                success:function(results){
                                    cb.call(that, eval(JSON.stringify(results)));
                                },
                                error: function(m,r) {
                                    notify("error","Error Occurred",$.parseJSON(r.responseText).message,0);
                                    console.log($.parseJSON(r.responseText).message);
                                }
                            });
                        }
                        $("#sch-gp-node").off("click").on("click", function(){
                            if($('.groups-tree-search').hasClass('collapse')){
                                openGroupSearch();
                            }else{
                                hideGroupSearch();
                            }
                        });
                        $("#sch-gp-node").removeClass('disabled');
                        $("#add-gp-node").removeClass('disabled');
                        $(".groups-tree-search").off().on("submit",function(e){
                            var searchval = $(this).find('input').val();
                            if(searchval != 'undefined' && searchval != null && searchval != ''){
                                window.groupsTreeApp.search.render({searchTerm:searchval});
                            }
                            e.preventDefault();
                        });

                    },
                    "check_callback" : true,
                    "multiple" : false
                },
                "dnd" : {
                    "copy" : true
                }
            });
            // this.$el.slimScroll({
            //     size: '7px',
            //     color: '#a1b2bd',
            //     opacity: .3,
            //     position: Metronic.isRTL() ? 'left' : 'right',
            //     height: $('.page-content').height(),
            //     allowPageScroll: false,
            //     disableFadeOut: false
            // });
            $("#add-gp-node").off("click").on("click", function(){create_node();});
            $("#del-gp-node").off("click").on("click", function(){delete_node();});
            $("#rnm-gp-node").off("click").on("click", function(){rename_node();});
        },
        initialize : function() {
            var self = this;
            if(window.selectedNodeId != 'undefined' && window.selectedNodeId != null){
                this.$el.unbind("move_node.jstree");
                //this.collection.fetch({ data: $.param({ page: self.page, page_limit: self.page_limit, q:self.term}) }).done(function() {
                this.itemList.fetch().done(function(flatjson){
                    self.collection.fetch({data : $.param({plorg_id : window.selectedNodeId})}).done(function(results) {
                        self.render();
                        self.el.itemList = self.itemList;
                        self.el.collection = self.collection;
                    });
                });
                this.$el.off("create.jstree").on("create.jstree", this.updateView);
                this.$el.off("changed.jstree").on("changed.jstree", this.collection.nodeChanged);
                this.$el.off("rename_node.jstree").on("rename_node.jstree", this.collection.renameNode);
                this.$el.off("move_node.jstree").on("move_node.jstree", this.collection.moveNode);
                this.$el.off("create_node.jstree").on("create_node.jstree", this.collection.createNode);
                this.$el.off("delete_node.jstree").on("delete_node.jstree", this.collection.deleteNode);
                this.$el.off("refresh_node.jstree").on("refresh_node.jstree", this.collection.refreshNode);
                $('#groups-tree').on("click", function(e){
                    if (e.originalEvent.target.className.indexOf("jstree-clicked") <= 0){
                        $('#groups-tree').jstree('deselect_all');
                        $('#rnm-gp-node').addClass('disabled');
                        $('#del-gp-node').addClass('disabled');
                        clearInfoPanel();
                    }
                });
            }
        },
        updateView : function (evt, context) {
            console.log(evt);
            console.log(context);
        },
        detailGroup:function(e, o){
            var self = this;
            window.groupsTreeApp.membersPage
            // window.groupsTreeApp.id = $(e.currentTarget).attr('ref');
            var nodeId = o.node.id;
            var parent = o.node.parent;
            if(nodeId.match(/j2_/g) == "" || nodeId.match(/j2_/g) == null){
                $('.block-sidebar').html(_.template($("#data-template-info-panel-loading").html(), {infopaneltitle: "Group Details"}));
                $('#content-adjustable').removeClass().addClass('col-md-8');
                $('#info-panel').removeClass().addClass('col-md-4');
                if(parent == '#'){
                    var cparam = $.param({plorg_id : window.selectedNodeId});
                }else{
                    var cparam = $.param({id : parent,plorg_id : window.selectedNodeId});
                }
                Metronic.blockUI({
                    target:"#info-panel .portlet-body",
                    boxed:true,
                    message:"Loading..."
                });
                var detailCollection = new window.groupsTreeApp.Collections.GroupDetail;
                if(window.groupsTreeApp.xhr != 'undefined' && window.groupsTreeApp.xhr != null){
                    window.groupsTreeApp.xhr.abort();
                }
                window.groupsTreeApp.xhr = detailCollection.fetch({
                    url: window.groupsTreeApp.path+'/api/xxxx/'+nodeId,
                    data : cparam,
                    success:function(results){
                        window.groupsTreeApp.org.fetchXhr = self.groupMembers.fetch({
                            url:window.groupsTreeApp.path+'/api/xxxx/'+nodeId+'/xxxx',
                            data: $.param({limit : self.limit,page : window.groupsTreeApp.membersPage}),
                            success:function(members){
                                if(results.get(nodeId) == 'undefined' || results.get(nodeId) == null){
                                    detailCollection.fetch({
                                        data : cparam,
                                        success:function(results){
                                            var variables = {
                                                group:results.get(nodeId).toJSON(),
                                                members:self.groupMembers.toJSON()
                                            };
                                            var template = _.template($("#data-template-info-panel-group").html(), variables);
                                            this.$('.block-sidebar').html(template);
                                        }
                                    });
                                }else{
                                    var variables = {
                                        group:results.get(nodeId).toJSON(),
                                        members:self.groupMembers.toJSON()
                                    };
                                    var template = _.template($("#data-template-info-panel-group").html(), variables);
                                    this.$('.block-sidebar').html(template);
                                }
                                var variables = {
                                    total:self.groupMembers.getTotal(),
                                    lastPage:Math.ceil(self.groupMembers.getTotal()/self.limit),
                                    extremePagesLimit:3,
                                    nearbyPagesLimit:2,
                                    currentPage:window.groupsTreeApp.membersPage,
                                    paginationFunction:'paginateGroupMembers'
                                };
                                var template = _.template($("#data-template-pagination").html(), variables);
                                $("#p-members").append(template);
                            }
                        });
                    }
                });
            }
        },
        detailPaginate:function(){
            var self = this;
            nodeId = $('#groups-tree').jstree('get_selected');
            Metronic.blockUI({
                target:"#p-members",
                boxed:true,
                message:"Loading..."
            });
            self.groupMembers.fetch({
                url:window.groupsTreeApp.path+'/api/xxxx/'+nodeId+'/xxxx',
                data: $.param({limit : self.limit,page : window.groupsTreeApp.membersPage}),
                success:function(){
                    var variables = {
                        members:self.groupMembers.toJSON()
                    };
                    var template = _.template($("#data-template-info-panel-member-p").html(), variables);
                    this.$('#p-members').html(template);

                    var variables = {
                        total:self.groupMembers.getTotal(),
                        lastPage:Math.ceil(self.groupMembers.getTotal()/self.limit),
                        extremePagesLimit:3,
                        nearbyPagesLimit:2,
                        currentPage:window.groupsTreeApp.membersPage,
                        paginationFunction:'paginateGroupMembers'
                    };
                    var template = _.template($("#data-template-pagination").html(), variables);
                    $("#p-members").append(template);
                }
            });
        }
    });

    window.groupsTreeApp.Collections.GroupsSearch = Backbone.Collection.extend({
        url : window.groupsTreeApp.path+'/api/xxxx',
        initialize : function () {
            Backbone.Collection.prototype.initialize.apply(this, arguments);
        },
        parse:function(response){
            if(response.status == "ZERO_RESULTS"){
                return null;
            }
            return response.data;
        }
    });

    window.groupsTreeApp.Views.Search = Backbone.View.extend({
        el : $('#groups-results'),
        collection : new groupsTreeApp.Collections.GroupsSearch,
        render: function(params) {
            var self = this;
            // show results
            if(thisTreeApp.xhr != 'undefined' && thisTreeApp.xhr != null){
                thisTreeApp.xhr.abort();
            }
            this.$el.html('').removeClass('collapse');
            Metronic.blockUI({
                target:this.$el,
                boxed:true,
                message:"Searching"
            });
            thisTreeApp.xhr = this.collection.fetch({
                data: $.param({q : params.searchTerm, plorg_id:window.selectedNodeId, include_tree:1, limit:50}),
                success:function(results){
                    var variables = {
                        results:results.toJSON()
                    };

                    var template = _.template($("#data-template-groups-search").html(), variables);
                    self.$el.html(template);

                    $("#groups-results td").off("click").on("click",function(){
                        $('#groups-tree').jstree('deselect_all');
                        self.$el.addClass('collapse');
                        hideGroupSearch();
                        var nodeId = $(this).attr('rel');
                        var tree = self.collection.get(nodeId).toJSON().grouptree;
                        self.openNodes(tree);
                    });
                },
                error:function(e,f,g){
                    if(f.statusText != 'abort'){
                        this.$el.html('<table class="table"><tr><td>Error</td></tr></table>');
                    }
                }
            });
        },
        initialize : function(params) {
            var self = this;
            if(params != 'undefined' && params != null){
                self.render();
            }
        },
        openNodes : function(tree){
            var self = this;
            if(tree.length > 1){
                var node = tree.shift();
                $('#groups-tree').jstree('open_node', node.id, function(e){
                    setTimeout(function(){
                        $('#groups-tree').slimScroll({ scrollBy: $('#'+node.id).position().top+'px' });
                    },300 );
                    if(tree.length > 1){
                        self.openNodes(tree);
                    }else{
                        node = tree.shift();
                        $('#groups-tree').jstree('select_node', node);
                    }
                });
            }else{
                var node = tree.shift();
                $('#groups-tree').jstree('select_node', node);
            }
        }
    });

    // window.groupsTreeApp.init();
    window.groupsTreeApp.search = new window.groupsTreeApp.Views.Search;
    window.groupsTreeApp.org = new window.groupsTreeApp.Views.Treenode;
    window.groupsTreeApp.xhr;
    
});

function paginateGroupMembers(page){
    window.groupsTreeApp.membersPage = page;
    window.groupsTreeApp.org.detailPaginate();
}

function create_node(){
    var nodeId = null;
    if($('#groups-tree').jstree('get_selected') != 'undefined' && !$.isEmptyObject($('#groups-tree').jstree('get_selected'))){
        nodeId = $('#groups-tree').jstree('get_selected');
    }
    $('#groups-tree').jstree('create_node',nodeId,null,0);
}

function rename_node(){
    clearInfoPanel();
    var nodeId = null;
    if($('#groups-tree').jstree('get_selected') != 'undefined' && !$.isEmptyObject($('#groups-tree').jstree('get_selected'))){
        nodeId = $('#groups-tree').jstree('get_selected');
        node = $('#groups-tree').jstree('get_node', nodeId);
    }

    bootbox.dialog({
        message:"<form><div class='form-group'><label class='control-label'>Name:</label> <input name=\"newName\" value='"+node.text+"' id='form-focus' class='form-control' /></div><div class='form-group'><label class='control-label'>Description:</label> <input name=\"description\" value='"+node.data.description+"' class='form-control' /></div><input type='submit' style='display:none;'/></form><script>$('form').on('submit',function(){$('.btn-primary').click();return false;});</script>",
        title: "Edit group",
        buttons:{
            cancel: {
                label: "Cancel",
                className: "btn-cancel",
                callback: function(){
                    // $('#groups-tree').jstree('delete_node',context.node);
                }
            },
            create: {
                label: "Save",
                className: "btn btn-primary",
                callback: function(e){
                    var newName = $(e.target).parents('.modal-dialog').find('form input[name=newName]').val();
                    var description = $(e.target).parents('.modal-dialog').find('form input[name=description]').val();
                    if (newName === null || newName == 'undefined' || newName == '') {
                        $(e.target).parents('.modal-dialog').find('form input[name=newName]').parent().addClass('has-error');
                        $(e.target).parents('.modal-dialog').find('.help-block').remove();
                        $(e.target).parents('.modal-dialog').find('form input[name=newName]').parent().append('<span class="help-block">Name is required</span>');
                        // $('#groups-tree').jstree('delete_node',context.node);
                        e.stopImmediatePropagation();
                        return false;
                    } else {
                        $('#groups-tree').jstree('delete_node', $('#groups-tree').jstree('get_node','group-no-nodes'));
                        node.data = {"description":description};
                        if(!$('#groups-tree').jstree('rename_node',node,newName)){
                            $('#groups-tree').jstree('refresh_node',node);
                        }
                    }
                }
            }
        }
    }).after(function(){
        setTimeout("$('.modal-dialog').find('form input[name=newName]').focus()",500 );
    });
}

function delete_node(){
    clearInfoPanel();
    var nodeId = null;
    if($('#groups-tree').jstree('get_selected') != 'undefined' && !$.isEmptyObject($('#groups-tree').jstree('get_selected'))){
        nodeId = $('#groups-tree').jstree('get_selected');
    }
    $('#groups-tree').jstree('delete_node',nodeId);
}

function updateContent(){
    clearInfoPanel();
    $(".breadcrumb-org").html(window.selectedNodeName);
    $('#groups-tree').jstree('destroy',true);
    Metronic.blockUI({
        target:"#groups-tree",
        boxed:true,
        message:"Loading..."
    });
    hideGroupSearch();
    window.groupsTreeApp.org.initialize();
 }

function openGroupSearch(){
    $('.groups-tree-search').removeClass('collapse');
    $('.groups-tree-search .input-group .form-control').focus();
    $('#sch-gp-node i').addClass('fa-times').removeClass('fa-search');
    // $('.groups-tree-search .input-group .form-control').on('blur',function(){hideGroupSearch();});
}
function hideGroupSearch(){
    $('#sch-gp-node i').removeClass('fa-times').addClass('fa-search');
    $('.groups-tree-search').addClass('collapse');
    $('.groups-tree-search .input-group input').val('');
    $('#groups-results').addClass('collapse');
}