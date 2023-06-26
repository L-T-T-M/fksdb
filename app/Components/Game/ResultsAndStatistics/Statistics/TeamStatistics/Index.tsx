import ChartContainer from 'FKSDB/Components/Charts/Core/ChartContainer';
import { TeamModel } from 'FKSDB/Models/ORM/Models/Fyziklani/TeamModel';
import * as React from 'react';
import { connect } from 'react-redux';
import { Action, Dispatch } from 'redux';
import { setNewState } from '../../actions/stats';
import Legend from './Legend';
import PointsInTime from './LineChart';
import PointsPie from './PieChart';
import TimeLine from './Timeline';
import { Store } from 'FKSDB/Components/Game/ResultsAndStatistics/reducers/store';
import { TranslatorContext } from '@translator/LangContext';

interface StateProps {
    teams: TeamModel[];
    teamId: number;
}

interface DispatchProps {
    onChangeFirstTeam(id: number): void;
}

class TeamStats extends React.Component<StateProps & DispatchProps, never> {
    static contextType = TranslatorContext;

    public render() {
        const translator = this.context;
        const {teams, onChangeFirstTeam, teamId} = this.props;
        const selectedTeam = teams.filter((team) => {
            return team.teamId === teamId;
        })[0];
        return <>
            <div className="panel color-auto">
                <div className="container">
                    <h2>
                        {translator.getText('Statistic for team ') + (selectedTeam ? selectedTeam.name : '')}
                    </h2>
                    <p>
                        <select className="form-control" onChange={(event) => {
                            onChangeFirstTeam(+event.target.value);
                        }}>
                            <option value={null}>--{translator.getText('select team')}--</option>
                            {teams.map((team) => {
                                return (<option key={team.teamId} value={team.teamId}>{team.name}</option>);
                            })}
                        </select>
                    </p>
                </div>
            </div>
            {teamId && (<>
                <div className="panel color-auto">
                    <div className="container">
                        <h2>{translator.getText('Success of submitting')}</h2>
                        <ChartContainer
                            chart={PointsPie}
                            chartProps={{teamId}}
                            legendComponent={Legend}
                        />
                    </div>
                </div>
                <div className="panel color-auto">
                    <div className="container">
                        <h2>{translator.getText('Time progress')}</h2>
                        <ChartContainer
                            chart={PointsInTime}
                            chartProps={{teamId}}
                            legendComponent={Legend}
                        />
                    </div>
                </div>
                <div className="panel color-auto">
                    <div className="container">
                        <h2>{translator.getText('Timeline')}</h2>
                        <ChartContainer
                            chart={TimeLine}
                            chartProps={{teamId}}
                            legendComponent={Legend}
                        />
                    </div>
                </div>
            </>)}
        </>;
    }
}

const mapStateToProps = (state: Store): StateProps => {
    return {
        teamId: state.statistics.firstTeamId,
        teams: state.data.teams,
    };
};

const mapDispatchToProps = (dispatch: Dispatch<Action<string>>): DispatchProps => {
    return {
        onChangeFirstTeam: (teamId) => dispatch(setNewState({firstTeamId: +teamId})),
    };
};

export default connect(mapStateToProps, mapDispatchToProps)(TeamStats);
