import { UploadDataItem } from '@apps/ajaxUpload/middleware/uploadDataItem';
import { NetteActions } from '@appsCollector';
import { lang } from '@i18n/i18n';
import Card from '@shared/components/card';
import * as React from 'react';
import { connect } from 'react-redux';
import { Store } from '../../reducers';
import MessageBox from '../messageBox';
import File from './states/file';
import Form from './states/form';

interface OwnProps {
    accessKey: string;
    actions: NetteActions;
}

interface StateProps {
    submitting: boolean;
    submit: UploadDataItem;
}

class UploadContainer extends React.Component<OwnProps & StateProps, {}> {

    public render() {

        const {submit, accessKey} = this.props;
        const headline = (<>
            <h4>{submit.name}</h4>
            <small className="text-muted">{submit.deadline}</small>
        </>);
        const {} = this.props;
        return <div className="col-md-6 mb-3">
            <Card headline={headline} level={'info'}>
                <MessageBox accessKey={accessKey}/>
                {this.getInnerContainer()}
            </Card>
        </div>;
    }

    private getInnerContainer() {
        const {submit, submitting, actions, accessKey} = this.props;
        if (submit.disabled) {
            return <p className="alert alert-info">{lang.getText('Táto úloha nieje pre tvoju kategoriu')}</p>;
        }
        if (submitting) {
            return (<div className="text-center">
                <span className="d-block">{lang.getText('Loading')}</span>
                <span className="display-1 d-block"><i className="fa fa-spinner fa-spin "/></span>
            </div>);
        }
        if (submit.submitId) {
            return (<File actions={actions} accessKey={accessKey} submit={submit}/>);
        } else {
            return (<Form actions={actions} accessKey={accessKey} submit={submit}/>);
        }
    }
}

const mapStateToProps = (state: Store, ownProps: OwnProps): StateProps => {
    const {accessKey} = ownProps;
    return {
        submit: {
            ...state.uploadData,
        },
        submitting: state.fetchApi.hasOwnProperty(accessKey) ? state.fetchApi[accessKey].submitting : false,
    };
};

export default connect(mapStateToProps, null)(UploadContainer);
