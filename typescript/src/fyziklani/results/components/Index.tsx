import * as React from 'react';
import { Provider } from 'react-redux';
import {
    applyMiddleware,
    createStore,
} from 'redux';
import logger from 'redux-logger';
import { NetteActions } from '../../../app-collector/';
import { config } from '../../../config/';
import Downloader from '../../helpers/downloader/components/Index';
import { app } from '../reducers';
import App from './App';

interface Props {
    mode: string;
    actions: NetteActions;
}

export default class Index extends React.Component<Props, {}> {
    public render() {
        const store = config.dev ? createStore(app, applyMiddleware(logger)) : createStore(app);
        const accessKey = '@@fyziklani-results';
        const {mode, actions} = this.props;
        return (
            <Provider store={store}>
                <div className={'fyziklani-results'}>
                    <Downloader accessKey={accessKey} actions={actions}/>
                    <App mode={mode}/>
                </div>
            </Provider>
        );
    }
}
