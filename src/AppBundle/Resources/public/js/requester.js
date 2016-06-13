var UrlBox = React.createClass({
    loadUrlsFromServer: function() {
        $.ajax({
            url: this.props.url,
            dataType: 'json',
            cache: false,
            success: function(data) {
                this.setState({data: data});
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(this.props.home, status, err.toString());
            }.bind(this)
        });
    },

    updateScreen: function (data) {
        var current = this.state.data;
        if (current.length > 0) {
            for (var i = 0; i < current.length; i++) {
                if (current[i].name == data.name) {
                    current[i].status = data.status;
                    current[i].batch = data.batch;
                    break;
                }
            }
        } else {
            current[0] = data;
        }
        this.setState({data: current});
    },
    
    handleUrlsSubmit: function(url) {
        var urls = this.state.data;
        var newUrls = url.urls.split("\n");

        for (var i = 0; i < newUrls.length; i++) {
            newUrls[i] = {
                'name': newUrls[i],
                'status': 'Still working',
                'batch': 'Still working'
            };
        }
        var state = urls.concat(newUrls);

        this.setState({data: state});

        $.ajax({
            url: this.props.url,
            dataType: 'json',
            type: 'POST',
            data: url,
            success: function(data) {
                var urls = this.state.data;
                var newUrls = urls.concat(data);
                this.setState({data: newUrls});
            }.bind(this),
            error: function(xhr, status, err) {
                console.error(this.props.url, status, err.toString());
            }.bind(this)
        });
    },

    getInitialState: function() {
        return {data: []};
    },

    componentDidMount: function() {
        this.loadUrlsFromServer();
        var urlBox = this;
        var conn = new ab.Session('ws://172.17.0.2:8080',
            function() {
                conn.subscribe('url_info', function(topic, data) {
                    urlBox.updateScreen(data);
                });
            },
            function() {
                console.warn('WebSocket connection closed');
            },
            {'skipSubprotocolCheck': true}
        );
    },

    render: function() {
        return (
            <div className="urlBox">
                <h1>Urls</h1>
                <UrlList data={this.state.data} />
                <UrlForm onUrlsSubmit={this.handleUrlsSubmit} />
            </div>
        );
    }
});

var Url = React.createClass({
    render: function() {
        return (
            <div className="url">
                <a href={this.props.data.name} target="_blank">{this.props.data.name}</a>
                <p>Status: {this.props.data.status}</p>
                <p>Batch: {this.props.data.batch}</p>
            </div>
        );
    }
});

var UrlList = React.createClass({
    render: function() {
        return (
            <div className="urlList">
                {this.props.data.map(function (url) {
                    return  <Url data={url} key={url.name}/>
                })}
            </div>
        );
    }
});

var UrlForm = React.createClass({

    getInitialState: function() {
        return {author: '', text: '', urls: ''};
    },

    handleSubmit: function(e) {
        e.preventDefault();

        var urls = this.state.urls;

        if (!urls) {
            return;
        }

        this.props.onUrlsSubmit({urls: urls});
        this.setState({urls: ''});
        this.clearTextArea(document.getElementById('urls'));
    },

    clearTextArea: function(e) {
        e.value = "";
    },

    handleUrlsChange: function(e) {
        this.setState({urls: e.target.value});
    },

    render: function() {
        return (
            <form className="urlForm" onSubmit={this.handleSubmit}>
                <textarea
                    name="urls"
                    id="urls"
                    cols="30"
                    rows="10"
                    onChange={this.handleUrlsChange}
                >
                </textarea>
                <input type="submit" value="Send requests" />
            </form>
        );
    }
});

ReactDOM.render(
    <UrlBox url="http://172.17.0.2:8000/api/urls" />,
    document.getElementById('content')
);