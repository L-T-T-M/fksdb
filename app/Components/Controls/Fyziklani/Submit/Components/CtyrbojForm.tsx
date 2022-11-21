import { translator } from '@translator/translator';
import { SubmitFormRequest, submitStart } from 'FKSDB/Components/Controls/Fyziklani/Submit/actions';
import ScanInput from 'FKSDB/Components/Controls/Fyziklani/Submit/Components/Inputs/ScanInput';
import SubmitButtons from 'FKSDB/Components/Controls/Fyziklani/Submit/Components/Inputs/SubmitButtons';
import TextInput from 'FKSDB/Components/Controls/Fyziklani/Submit/Components/Inputs/TextInput';
import ErrorBlock from 'FKSDB/Components/Controls/Fyziklani/Submit/Components/Outputs/ErrorBlock';
import ValueDisplay from 'FKSDB/Components/Controls/Fyziklani/Submit/Components/Outputs/ValueDisplay';
import { Store as SubmitStore } from 'FKSDB/Components/Controls/Fyziklani/Submit/reducer';
import { Message } from 'vendor/fykosak/nette-frontend-component/src/Responses/response';
import { NetteActions } from 'vendor/fykosak/nette-frontend-component/src/NetteActions/netteActions';
import { DataResponse } from 'vendor/fykosak/nette-frontend-component/src/Responses/response';
import { ModelFyziklaniTask } from 'FKSDB/Models/ORM/Models/Fyziklani/modelFyziklaniTask';
import { ModelFyziklaniTeam } from 'FKSDB/Models/ORM/Models/Fyziklani/modelFyziklaniTeam';
import * as React from 'react';
import { connect } from 'react-redux';
import { Action, Dispatch } from 'redux';
import { Field, Form, formValueSelector, InjectedFormProps, reduxForm } from 'redux-form';
import { validate } from '../middleware';

export interface OwnProps {
    tasks: ModelFyziklaniTask[];
    teams: ModelFyziklaniTeam[];
    actions: NetteActions;
    availablePoints: number[];
}

interface DispatchProps {
    onSubmit(values: SubmitFormRequest): Promise<DataResponse<SubmitFormRequest>>;
}

interface StateProps {
    code: string;
    messages: Message[];
}

class CtyrbojForm extends React.Component<StateProps & OwnProps & DispatchProps & InjectedFormProps<{ code: string }, OwnProps>> {

    public render() {
        const {handleSubmit, onSubmit, tasks, teams, messages, code} = this.props;

        return (
            <Form onSubmit={handleSubmit(onSubmit)} onChange={handleSubmit(onSubmit)}>
                {messages.map((message, key) => {
                    return <div key={key} className={'alert alert-' + message.level}> {message.text}</div>;
                })}
                <div className="row">
                    <div className="col-lg-6 col-md-12 mb-3">
                        <h3>{translator.getText('Code')}</h3>
                        <div className="form-group">
                            <Field name="code" component={TextInput}/>
                        </div>
                        <div className="form-group">
                            <Field name="code" component={ErrorBlock}/>
                        </div>
                    </div>
                    <div className="col-lg-6 col-md-12 mb-3">
                        <Field name="code" component={ScanInput}/>
                    </div>
                </div>
                <hr/>
                <ValueDisplay code={code} tasks={tasks} teams={teams}/>
            </Form>
        );
    }
}

export const FORM_NAME = 'codeForm';

const mapDispatchToProps = (dispatch: Dispatch<Action>, ownProps: OwnProps): DispatchProps => {
    return {
        onSubmit: (values: SubmitFormRequest) => submitStart(dispatch, values, ownProps.actions.getAction('save')),
    };
};

const mapStateToProps = (state: SubmitStore): StateProps => {
    const selector = formValueSelector(FORM_NAME);
    return {
        code: selector(state, 'code'),
        messages: state.fetch.messages,
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(
    reduxForm<{ code: string }, OwnProps, string>({
        form: FORM_NAME,
        validate,
    })(CtyrbojForm),
);
