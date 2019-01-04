import * as React from 'react';
import { connect } from 'react-redux';
import Loading from '../../helpers/components/loading';
import { IFyziklaniResultsStore } from '../reducers';
import Results from './results/';
import FilterSelect from './results/filter/select';

interface IState {
    isReady?: boolean;
}

interface IProps {
    mode: string;
}

class App extends React.Component<IState & IProps, {}> {
    public render() {

        const {isReady, mode} = this.props;
        if (!isReady) {
            return <Loading/>;
        }
        return (<>
            <FilterSelect mode={mode}/>
            <Results mode={mode}/>
        </>);
    }
}

const mapStateToProps = (state: IFyziklaniResultsStore): IState => {
    return {
        isReady: state.options.isReady,
    };
};

export default connect(mapStateToProps, null)(App);
